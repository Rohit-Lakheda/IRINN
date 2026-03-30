<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddMoneyRequest;
use App\Http\Requests\CreateWalletRequest;
use App\Http\Requests\WalletPaymentRequest;
use App\Models\Application;
use App\Models\PaymentTransaction;
use App\Models\Registration;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\PayuService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WalletController extends Controller
{
    protected WalletService $walletService;

    protected PayuService $payuService;

    public function __construct(WalletService $walletService, PayuService $payuService)
    {
        $this->walletService = $walletService;
        $this->payuService = $payuService;
    }

    /**
     * Wallet dashboard (syncs balance from PayU on load, shows recent transactions).
     */
    public function index(): View|RedirectResponse
    {
        $userId = session('user_id');
        if (! $userId) {
            return redirect()->route('user.login')->with('error', 'Please login to access your wallet.');
        }

        $user = Registration::find($userId);
        if (! $user) {
            return redirect()->route('user.login')->with('error', 'User not found.');
        }

        // Get or create wallet
        $wallet = $user->wallet;
        if (! $wallet) {
            return redirect()->route('user.wallet.create')->with('info', 'Please create a wallet first.');
        }

        // Sync balance from PayU when user visits dashboard
        try {
            $this->walletService->syncBalance($wallet);
        } catch (\Exception $e) {
            Log::error('Wallet balance sync failed', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Sync missing transactions
        try {
            $this->walletService->syncTransactionsFromPayU($wallet);
        } catch (\Exception $e) {
            Log::error('Wallet transactions sync failed', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Refresh wallet to get updated balance
        $wallet->refresh();

        // Get recent transactions
        $recentTransactions = $wallet->transactions()
            ->latest()
            ->limit(10)
            ->get();

        return view('user.wallet.index', [
            'wallet' => $wallet,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    /**
     * Show wallet creation form.
     */
    public function create(): View|RedirectResponse
    {
        $userId = session('user_id');
        if (! $userId) {
            return redirect()->route('user.login')->with('error', 'Please login to create a wallet.');
        }

        $user = Registration::find($userId);
        if (! $user) {
            return redirect()->route('user.login')->with('error', 'User not found.');
        }

        // Check if user already has a wallet
        if ($user->wallet) {
            return redirect()->route('user.wallet.index')->with('info', 'You already have a wallet.');
        }

        return view('user.wallet.create');
    }

    /**
     * Create wallet via PayU API, records creation transaction.
     */
    public function store(CreateWalletRequest $request): RedirectResponse
    {
        $userId = session('user_id');
        if (! $userId) {
            return redirect()->route('user.login')->with('error', 'Please login to create a wallet.');
        }

        $user = Registration::find($userId);
        if (! $user) {
            return redirect()->route('user.login')->with('error', 'User not found.');
        }

        // Check if user already has a wallet
        if ($user->wallet) {
            return redirect()->route('user.wallet.index')->with('info', 'You already have a wallet.');
        }

        try {
            // Always use closed_loop wallet type
            $walletType = 'closed_loop';
            $userData = [
                'email' => $user->email,
                'firstname' => $user->fullname,
                'phone' => $user->mobile,
            ];

            Log::info('Attempting to create wallet', [
                'user_id' => $userId,
                'wallet_type' => $walletType,
            ]);

            $wallet = $this->walletService->createWallet($userId, $walletType, $userData);

            if (! $wallet) {
                $errorMessage = $this->walletService->getLastError() ?? 'Failed to create wallet. Please check logs for details.';
                Log::error('Wallet creation returned null', [
                    'user_id' => $userId,
                    'last_error' => $this->walletService->getLastError(),
                ]);

                return redirect()->back()
                    ->withInput()
                    ->with('error', $errorMessage);
            }

            return redirect()->route('user.wallet.index')->with('success', 'Wallet created successfully!');
        } catch (\Exception $e) {
            Log::error('Wallet creation exception', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Failed to create wallet: '.$e->getMessage());
        }
    }

    /**
     * Show add money form.
     */
    public function addMoney(): View|RedirectResponse
    {
        $userId = session('user_id');
        if (! $userId) {
            return redirect()->route('user.login')->with('error', 'Please login to add money.');
        }

        $user = Registration::find($userId);
        if (! $user || ! $user->wallet) {
            return redirect()->route('user.wallet.create')->with('error', 'Please create a wallet first.');
        }

        $wallet = $user->wallet;
        if (! $wallet->isActive()) {
            return redirect()->route('user.wallet.index')->with('error', 'Your wallet is not active.');
        }

        // Calculate total billing cycle amount for all live applications
        $totalBillingCycleAmount = $this->calculateTotalBillingCycleAmount($user);
        $currentBalance = (float) $wallet->balance;

        // Calculate minimum amount to add based on current balance
        // If current balance < total billing cycle amount, user must add enough to cover the difference
        // If current balance >= total billing cycle amount, user can add any amount (minimum ₹1)
        $minimumAmountToAdd = 0;
        if ($totalBillingCycleAmount > 0 && $currentBalance < $totalBillingCycleAmount) {
            // Need to add at least: (required amount - current balance)
            $minimumAmountToAdd = $totalBillingCycleAmount - $currentBalance;
        } else {
            // Current balance is sufficient, can add any amount (minimum ₹1)
            $minimumAmountToAdd = 1;
        }

        return view('user.wallet.add-money', [
            'wallet' => $wallet,
            'totalBillingCycleAmount' => $totalBillingCycleAmount,
            'minimumAmountToAdd' => $minimumAmountToAdd,
            'currentBalance' => $currentBalance,
        ]);
    }

    /**
     * Process wallet top-up, records credit transaction.
     */
    public function processAddMoney(AddMoneyRequest $request): RedirectResponse|View|Response
    {
        $userId = session('user_id');
        if (! $userId) {
            return redirect()->route('user.login')->with('error', 'Please login to add money.');
        }

        $user = Registration::find($userId);
        if (! $user || ! $user->wallet) {
            return redirect()->route('user.wallet.create')->with('error', 'Please create a wallet first.');
        }

        $wallet = $user->wallet;
        if (! $wallet->isActive()) {
            return redirect()->route('user.wallet.index')->with('error', 'Your wallet is not active.');
        }

        $amount = (float) $request->input('amount');

        // Generate transaction ID
        $transactionId = 'WLT'.time().rand(1000, 9999);

        // Create payment transaction for add money
        $paymentTransaction = PaymentTransaction::create([
            'user_id' => $userId,
            'application_id' => null,
            'transaction_id' => $transactionId,
            'payment_mode' => config('services.payu.mode', 'test'),
            'payment_status' => 'pending',
            'amount' => $amount,
            'currency' => 'INR',
            'product_info' => 'Wallet Top-up',
        ]);

        // Prepare payment data for PayU
        $paymentData = $this->payuService->preparePaymentData([
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'product_info' => 'Wallet Top-up',
            'firstname' => $user->fullname,
            'email' => $user->email,
            'phone' => $user->mobile,
            'success_url' => url(route('user.wallet.add-money-success', [], false)),
            'failure_url' => url(route('user.wallet.add-money-failure', [], false)),
            'udf1' => 'wallet_topup',
            'udf2' => (string) $wallet->id,
            'udf3' => (string) $paymentTransaction->id,
        ]);

        // Store payment data in cookies for callback (session gets cleared when PayU redirects)
        $cookieData = [
            'payment_transaction_id' => $paymentTransaction->id,
            'transaction_id' => $transactionId,
            'wallet_id' => $wallet->id,
            'user_id' => $userId,
            'amount' => $amount,
        ];

        // Store user session data for login restoration after PayU redirect
        $userSessionData = [
            'user_id' => $userId,
            'user_email' => $user->email,
            'user_name' => $user->fullname,
            'user_registration_id' => $user->registrationid,
        ];

        // Set cookies with payment details and user session (expires in 1 hour)
        $response = response()->view('user.payments.redirect-payu', [
            'paymentUrl' => $this->payuService->getPaymentUrl(),
            'paymentData' => $paymentData,
        ]);

        $response->cookie(
            'wallet_topup_payment_data',
            json_encode($cookieData),
            60,
            '/',
            null,
            true,
            false,
            false,
            'lax'
        );
        $response->cookie(
            'user_session_data',
            json_encode($userSessionData),
            60,
            '/',
            null,
            true,
            false,
            false,
            'lax'
        );

        return $response;
    }

    /**
     * Handle add money success callback.
     */
    public function addMoneySuccess(Request $request): RedirectResponse|\Illuminate\Http\Response
    {
        // Auto-refresh mechanism: Check session count
        // If session count doesn't exist or is 0, set it to 1 and refresh the page
        // If session count = 1, skip refresh and process payment
        $redirectCount = session('wallet_payment_redirect_count', 0);

        if ($redirectCount === 0) {
            // First visit: Set redirect count to 1 and auto-refresh the page
            session(['wallet_payment_redirect_count' => 1]);
            session()->save();

            Log::info('Wallet Top-up Success - First visit, setting session count and auto-refreshing', [
                'current_url' => $request->fullUrl(),
            ]);

            // Show "fetching payment details" message and auto-refresh
            return response()->view('user.wallet.payment-processing', [
                'message' => 'Fetching payment details...',
                'submessage' => 'Please do not refresh or go back. You will be redirected automatically.',
                'redirectUrl' => $request->fullUrl(),
                'autoRefresh' => true,
            ]);
        }

        // Second visit (redirect_count = 1): Process payment and redirect to final destination
        // Clear the redirect count so it doesn't interfere with future requests
        session()->forget('wallet_payment_redirect_count');
        session()->save();

        // PayU may send data via POST or GET (query string)
        $response = array_merge($request->query(), $request->post());

        Log::info('=== Wallet Top-up Success Callback Method Called (Second Visit) ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'has_query' => ! empty($request->query()),
            'has_post' => ! empty($request->post()),
            'has_cookie' => $request->hasCookie('wallet_topup_payment_data'),
            'has_user_session_cookie' => $request->hasCookie('user_session_data'),
            'redirect_count' => $redirectCount,
        ]);

        try {
            // Get all data from cookies FIRST (session is cleared when PayU redirects)
            // Cookies are the source of truth for payment callbacks
            $cookieData = null;
            $userSessionData = null;

            // Get payment transaction data from cookie
            if ($request->hasCookie('wallet_topup_payment_data')) {
                $cookieData = json_decode($request->cookie('wallet_topup_payment_data'), true);
                Log::info('Wallet Top-up Success - Found payment data in cookie', [
                    'cookie_data' => $cookieData,
                ]);
            }

            // Get user session data from cookie
            if ($request->hasCookie('user_session_data')) {
                $userSessionData = json_decode($request->cookie('user_session_data'), true);
                Log::info('Wallet Top-up Success - Found user session data in cookie', [
                    'has_user_id' => isset($userSessionData['user_id']),
                ]);
            }

            // If no cookie data, try to get from PayU response
            if (! $cookieData) {
                $transactionId = $response['txnid'] ?? $request->input('txnid');
                $paymentTransactionId = $response['udf3'] ?? $request->input('udf3');
                $walletId = $response['udf2'] ?? $request->input('udf2');

                if ($paymentTransactionId) {
                    $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
                    if ($paymentTransaction) {
                        $cookieData = [
                            'payment_transaction_id' => $paymentTransaction->id,
                            'transaction_id' => $paymentTransaction->transaction_id,
                            'wallet_id' => $walletId,
                            'user_id' => $paymentTransaction->user_id,
                            'amount' => $paymentTransaction->amount,
                        ];
                    }
                } elseif ($transactionId) {
                    $paymentTransaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
                    if ($paymentTransaction) {
                        $cookieData = [
                            'payment_transaction_id' => $paymentTransaction->id,
                            'transaction_id' => $paymentTransaction->transaction_id,
                            'wallet_id' => $walletId,
                            'user_id' => $paymentTransaction->user_id,
                            'amount' => $paymentTransaction->amount,
                        ];
                    }
                }
            }

            if (! $cookieData) {
                Log::error('Wallet Top-up Success - No payment data found in cookie or response', [
                    'response' => $response,
                ]);

                // No payment data - don't add money, redirect with error
                $errorMessage = 'Payment information not found. Please contact support with your transaction details.';
                $response = redirect()->route('user.login-from-cookie', [
                    'redirect' => route('user.dashboard'),
                    'error' => urlencode($errorMessage),
                ])->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');

                return $response;
            }

            // Find payment transaction
            $paymentTransaction = PaymentTransaction::find($cookieData['payment_transaction_id']);

            if (! $paymentTransaction) {
                Log::error('Wallet Top-up Success - Payment transaction not found', [
                    'payment_transaction_id' => $cookieData['payment_transaction_id'],
                ]);

                // Payment transaction not found - don't add money, redirect with error
                $errorMessage = 'Payment transaction not found. Please contact support.';
                $response = redirect()->route('user.login-from-cookie', [
                    'redirect' => route('user.dashboard'),
                    'error' => urlencode($errorMessage),
                ])->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');

                return $response;
            }

            // If payment is already processed, redirect immediately
            if ($paymentTransaction->payment_status === 'success') {
                Log::info('Wallet Top-up Success - Payment already processed', [
                    'transaction_id' => $paymentTransaction->transaction_id,
                    'payment_status' => $paymentTransaction->payment_status,
                ]);

                // Payment already processed - redirect to dashboard
                $successMessage = 'Payment was already processed. Transaction ID: '.$paymentTransaction->transaction_id;
                $response = redirect()->route('user.login-from-cookie', [
                    'redirect' => route('user.dashboard'),
                    'success' => urlencode($successMessage),
                ])->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');

                return $response;
            }

            // Extract all PayU response fields (like invoice payments)
            $payuResponseFields = $this->extractPayuResponseFields($request);
            $transactionId = $cookieData['transaction_id'];

            // If PayU didn't send parameters, use Verify Payment API to get transaction status
            if (empty($payuResponseFields) || ! isset($payuResponseFields['status'])) {
                Log::info('Wallet Top-up Success - No parameters received, checking transaction status via API', [
                    'transaction_id' => $transactionId,
                ]);

                $verifyResponse = $this->payuService->checkTransactionStatus($transactionId);

                if ($verifyResponse && isset($verifyResponse['transaction_status'])) {
                    // Map Verify API response to our format
                    $payuResponseFields = [
                        'mihpayid' => $verifyResponse['mihpayid'] ?? null,
                        'txnid' => $transactionId,
                        'status' => $verifyResponse['transaction_status'] ?? 'success',
                        'unmappedstatus' => $verifyResponse['unmappedstatus'] ?? null,
                        'bank_ref_num' => $verifyResponse['bank_ref_num'] ?? null,
                        'mode' => $verifyResponse['mode'] ?? null,
                        'amount' => $verifyResponse['amount'] ?? $paymentTransaction->amount,
                        'error_code' => $verifyResponse['error_code'] ?? null,
                        'error_Message' => $verifyResponse['error_message'] ?? null,
                        'raw_response' => $verifyResponse,
                        'source' => 'verify_api',
                    ];
                }
            }

            // Verify hash if PayU sent parameters
            if (! empty($payuResponseFields) && isset($payuResponseFields['hash'])) {
                $isValid = $this->payuService->verifyHash($payuResponseFields);

                if (! $isValid) {
                    Log::warning('Wallet Top-up Success - Hash verification failed', [
                        'response' => $payuResponseFields,
                        'transaction_id' => $transactionId,
                    ]);
                }
            }

            // Check payment status
            $status = strtolower($payuResponseFields['status'] ?? '');
            $paymentStatus = 'failed';
            if (in_array($status, ['success', 'successful', 'completed'])) {
                $paymentStatus = 'success';
            }

            if ($paymentStatus !== 'success') {
                Log::error('Wallet Top-up Success - Payment status is not success', [
                    'transaction_id' => $transactionId,
                    'status' => $status,
                    'payu_response' => $payuResponseFields,
                ]);

                // Payment failed - don't add money, redirect with error
                $errorMessage = 'Payment verification failed. Please contact support.';
                $response = redirect()->route('user.login-from-cookie', [
                    'redirect' => route('user.dashboard'),
                    'error' => urlencode($errorMessage),
                ])->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');

                return $response;
            }

            // Build response message
            $payuPaymentId = $payuResponseFields['mihpayid'] ?? null;
            $responseMessage = 'Payment successful';
            $unmappedStatus = $payuResponseFields['unmappedstatus'] ?? null;
            $bankRefNum = $payuResponseFields['bank_ref_num'] ?? null;
            $mode = $payuResponseFields['mode'] ?? null;

            if ($unmappedStatus) {
                $responseMessage .= ' ('.$unmappedStatus.')';
            }
            if ($bankRefNum) {
                $responseMessage .= ' - Bank Ref: '.$bankRefNum;
            }
            if ($mode) {
                $responseMessage .= ' - Mode: '.$mode;
            }

            // Update payment transaction with all PayU response details (like invoice payments)
            $paymentTransaction->update([
                'payment_id' => $payuPaymentId ?? $paymentTransaction->payment_id,
                'payment_status' => 'success',
                'response_message' => $responseMessage,
                'payu_response' => $payuResponseFields, // Store all PayU response fields
                'hash' => $payuResponseFields['hash'] ?? null,
            ]);

            Log::info('Wallet Top-up Success - Payment transaction updated', [
                'transaction_id' => $transactionId,
                'payment_transaction_id' => $paymentTransaction->id,
                'payu_payment_id' => $payuPaymentId,
            ]);

            // Get wallet
            $walletId = $cookieData['wallet_id'];
            $wallet = Wallet::find($walletId);

            if (! $wallet) {
                Log::error('Wallet Top-up Success - Wallet not found', [
                    'wallet_id' => $walletId,
                ]);

                // Wallet not found - don't add money, redirect with error
                $errorMessage = 'Wallet not found. Please contact support.';
                $response = redirect()->route('user.login-from-cookie', [
                    'redirect' => route('user.dashboard'),
                    'error' => urlencode($errorMessage),
                ])->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');

                return $response;
            }

            // Add money to wallet with full PayU response (this will record credit transaction)
            $amount = (float) ($payuResponseFields['amount'] ?? $paymentTransaction->amount ?? 0);
            $success = $this->walletService->addMoney($wallet, $amount, $transactionId, $paymentTransaction->id, $payuResponseFields);

            if ($success) {
                Log::info('Wallet Top-up Success - Money added successfully', [
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'transaction_id' => $transactionId,
                ]);

                // Clear cookies
                $response = redirect()->route('user.login-from-cookie', [
                    'redirect' => route('user.dashboard'),
                    'success' => urlencode('Money added to wallet successfully! Transaction ID: '.$transactionId),
                ])->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');

                return $response;
            }

            Log::error('Wallet Top-up Success - Failed to add money to wallet', [
                'wallet_id' => $wallet->id,
                'amount' => $amount,
            ]);

            $errorMessage = 'Failed to add money to wallet. Please contact support.';
            $loginUrl = route('user.login-from-cookie', [
                'redirect' => route('user.wallet.index'),
                'error' => urlencode($errorMessage),
            ]);

            return redirect($loginUrl)
                ->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');
        } catch (\Exception $e) {
            Log::error('Wallet Top-up Success Callback Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Clear redirect count on error
            session()->forget('wallet_payment_redirect_count');
            session()->save();

            // Error occurred - don't add money, redirect with error
            $errorMessage = 'An error occurred while processing payment. Please contact support.';
            $response = redirect()->route('user.login-from-cookie', [
                'redirect' => route('user.dashboard'),
                'error' => urlencode($errorMessage),
            ])->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');

            return $response;
        }
    }

    /**
     * Handle add money failure callback.
     */
    public function addMoneyFailure(Request $request): RedirectResponse|\Illuminate\Http\Response
    {
        // Auto-refresh mechanism: Check session count
        $redirectCount = session('wallet_payment_redirect_count', 0);

        if ($redirectCount === 0) {
            // First visit: Set redirect count to 1 and auto-refresh the page
            session(['wallet_payment_redirect_count' => 1]);
            session()->save();

            Log::info('Wallet Top-up Failure - First visit, setting session count and auto-refreshing', [
                'current_url' => $request->fullUrl(),
            ]);

            return response()->view('user.wallet.payment-processing', [
                'message' => 'Processing payment status...',
                'submessage' => 'Please do not refresh or go back.',
                'redirectUrl' => $request->fullUrl(),
                'autoRefresh' => true,
            ]);
        }

        // Second visit: Process failure
        session()->forget('wallet_payment_redirect_count');
        session()->save();

        Log::info('=== Wallet Top-up Failure Callback Method Called (Second Visit) ===', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'has_query' => ! empty($request->query()),
            'has_post' => ! empty($request->post()),
            'has_cookie' => $request->hasCookie('wallet_topup_payment_data'),
            'has_user_session_cookie' => $request->hasCookie('user_session_data'),
            'redirect_count' => $redirectCount,
        ]);

        // PayU may send data via POST or GET (query string)
        $response = array_merge($request->query(), $request->post());

        try {
            // Get all data from cookies FIRST (session is cleared when PayU redirects)
            $cookieData = null;

            // Get payment transaction data from cookie
            if ($request->hasCookie('wallet_topup_payment_data')) {
                $cookieData = json_decode($request->cookie('wallet_topup_payment_data'), true);
                Log::info('Wallet Top-up Failure - Found payment data in cookie', [
                    'cookie_data' => $cookieData,
                ]);
            }

            // If no cookie data, try to get from PayU response
            if (! $cookieData) {
                $transactionId = $response['txnid'] ?? $request->input('txnid');
                $paymentTransactionId = $response['udf3'] ?? $request->input('udf3');

                if ($paymentTransactionId) {
                    $paymentTransaction = PaymentTransaction::find($paymentTransactionId);
                    if ($paymentTransaction) {
                        $cookieData = [
                            'payment_transaction_id' => $paymentTransaction->id,
                            'transaction_id' => $paymentTransaction->transaction_id,
                        ];
                    }
                } elseif ($transactionId) {
                    $paymentTransaction = PaymentTransaction::where('transaction_id', $transactionId)->first();
                    if ($paymentTransaction) {
                        $cookieData = [
                            'payment_transaction_id' => $paymentTransaction->id,
                            'transaction_id' => $paymentTransaction->transaction_id,
                        ];
                    }
                }
            }

            // Update payment transaction status to failed
            if ($cookieData && isset($cookieData['payment_transaction_id'])) {
                $paymentTransaction = PaymentTransaction::find($cookieData['payment_transaction_id']);
                if ($paymentTransaction) {
                    $paymentTransaction->update([
                        'payment_status' => 'failed',
                        'payu_response' => $response,
                    ]);
                }
            }

            // Payment failed - don't add money, redirect with error
            $errorMessage = 'Payment failed. Please try again or contact support if the amount was deducted.';
            $response = redirect()->route('user.login-from-cookie', [
                'redirect' => route('user.dashboard'),
                'error' => urlencode($errorMessage),
            ])->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');

            return $response;
        } catch (\Exception $e) {
            Log::error('Wallet Top-up Failure Callback Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Error occurred - don't add money, redirect with error
            $errorMessage = 'An error occurred. Please contact support.';
            $response = redirect()->route('user.login-from-cookie', [
                'redirect' => route('user.dashboard'),
                'error' => urlencode($errorMessage),
            ])->cookie('wallet_topup_payment_data', '', -1, '/', null, true, false, false, 'lax');

            return $response;
        }
    }

    /**
     * Use wallet for payment, records debit transaction.
     */
    public function makePayment(WalletPaymentRequest $request): JsonResponse|RedirectResponse
    {
        $userId = session('user_id');
        if (! $userId) {
            return redirect()->route('user.login')->with('error', 'Please login to make payment.');
        }

        $user = Registration::find($userId);
        if (! $user || ! $user->wallet) {
            return redirect()->route('user.wallet.create')->with('error', 'Please create a wallet first.');
        }

        $wallet = $user->wallet;
        if (! $wallet->isActive()) {
            return redirect()->route('user.wallet.index')->with('error', 'Your wallet is not active.');
        }

        $applicationId = (int) $request->input('application_id');
        $amount = (float) $request->input('amount');

        // Verify application belongs to user
        $application = Application::where('id', $applicationId)
            ->where('user_id', $userId)
            ->first();

        if (! $application) {
            return redirect()->back()->with('error', 'Invalid application.');
        }

        // Check wallet balance
        if (! $wallet->canDebit($amount)) {
            return redirect()->back()->with('error', 'Insufficient wallet balance.');
        }

        // Generate transaction ID
        $transactionId = 'WLT-PAY'.time().rand(1000, 9999);

        // Create payment transaction
        $paymentTransaction = PaymentTransaction::create([
            'user_id' => $userId,
            'application_id' => $applicationId,
            'transaction_id' => $transactionId,
            'payment_mode' => config('services.payu.mode', 'test'),
            'payment_status' => 'pending',
            'amount' => $amount,
            'currency' => 'INR',
            'product_info' => 'Application Payment via Wallet',
        ]);

        // Debit wallet (this will record debit transaction)
        $success = $this->walletService->debitWallet(
            $wallet,
            $amount,
            $transactionId,
            $paymentTransaction->id,
            $applicationId
        );

        if (! $success) {
            $paymentTransaction->update(['payment_status' => 'failed']);

            return redirect()->back()->with('error', 'Payment failed. Please try again.');
        }

        // Update payment transaction
        $paymentTransaction->update([
            'payment_status' => 'success',
            'payment_id' => $transactionId,
        ]);

        // Update application payment status if needed
        // (Add your application payment update logic here)

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Payment successful!',
                'transaction_id' => $transactionId,
            ]);
        }

        return redirect()->route('user.wallet.index')->with('success', 'Payment successful!');
    }

    /**
     * Transaction history (syncs missing transactions from PayU).
     */
    public function transactions(Request $request): View
    {
        $userId = session('user_id');
        if (! $userId) {
            return redirect()->route('user.login')->with('error', 'Please login to view transactions.');
        }

        $user = Registration::find($userId);
        if (! $user || ! $user->wallet) {
            return redirect()->route('user.wallet.create')->with('error', 'Please create a wallet first.');
        }

        $wallet = $user->wallet;

        // Sync missing transactions
        try {
            $this->walletService->syncTransactionsFromPayU($wallet);
        } catch (\Exception $e) {
            Log::error('Wallet transactions sync failed', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Get transactions with pagination
        $transactions = $wallet->transactions()
            ->latest()
            ->paginate(20);

        return view('user.wallet.transactions', [
            'wallet' => $wallet,
            'transactions' => $transactions,
        ]);
    }

    /**
     * API endpoint for balance check (syncs from PayU).
     */
    public function balance(): JsonResponse
    {
        $userId = session('user_id');
        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = Registration::find($userId);
        if (! $user || ! $user->wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found',
            ], 404);
        }

        $wallet = $user->wallet;

        // Sync balance from PayU
        try {
            $this->walletService->syncBalance($wallet);
            $wallet->refresh();
        } catch (\Exception $e) {
            Log::error('Wallet balance sync failed', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'balance' => (float) $wallet->balance,
            'currency' => $wallet->currency,
            'status' => $wallet->status,
        ]);
    }

    /**
     * Manual balance sync endpoint.
     */
    public function syncBalance(): JsonResponse|RedirectResponse
    {
        $userId = session('user_id');
        if (! $userId) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            return redirect()->route('user.login')->with('error', 'Please login to sync balance.');
        }

        $user = Registration::find($userId);
        if (! $user || ! $user->wallet) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Wallet not found',
                ], 404);
            }

            return redirect()->route('user.wallet.create')->with('error', 'Please create a wallet first.');
        }

        $wallet = $user->wallet;

        try {
            $success = $this->walletService->syncBalance($wallet);
            $wallet->refresh();

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => $success,
                    'balance' => (float) $wallet->balance,
                    'message' => $success ? 'Balance synced successfully' : 'Balance sync failed',
                ]);
            }

            return redirect()->route('user.wallet.index')->with(
                $success ? 'success' : 'error',
                $success ? 'Balance synced successfully!' : 'Failed to sync balance.'
            );
        } catch (\Exception $e) {
            Log::error('Manual wallet balance sync failed', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Balance sync failed: '.$e->getMessage(),
                ], 500);
            }

            return redirect()->route('user.wallet.index')->with('error', 'Failed to sync balance: '.$e->getMessage());
        }
    }

    /**
     * Handle PayU wallet transaction webhook.
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            Log::info('PayU Wallet Webhook Received', [
                'request_data' => $request->all(),
                'headers' => $request->headers->all(),
            ]);

            // Extract webhook data
            $transactionId = $request->input('transaction_id');
            $walletId = $request->input('wallet_id');
            $status = $request->input('status');
            $amount = $request->input('amount');
            $transactionType = $request->input('transaction_type'); // credit, debit, refund

            if (! $transactionId || ! $walletId) {
                Log::error('PayU Wallet Webhook - Missing required fields', [
                    'transaction_id' => $transactionId,
                    'wallet_id' => $walletId,
                ]);

                return response()->json(['status' => 'error', 'message' => 'Missing required fields'], 400);
            }

            // Find wallet
            $wallet = Wallet::where('wallet_id', $walletId)->first();
            if (! $wallet) {
                Log::error('PayU Wallet Webhook - Wallet not found', [
                    'wallet_id' => $walletId,
                ]);

                return response()->json(['status' => 'error', 'message' => 'Wallet not found'], 404);
            }

            // Verify hash if provided
            $receivedHash = $request->input('hash');
            if ($receivedHash) {
                // Verify hash (implement based on PayU wallet webhook hash format)
                // This is a placeholder - adjust based on actual PayU wallet webhook hash format
                $hashString = config('services.payu.salt').'|'.$transactionId.'|'.$walletId.'|'.$status.'|'.$amount;
                $calculatedHash = strtolower(hash('sha512', $hashString));

                if (! hash_equals($calculatedHash, strtolower($receivedHash))) {
                    Log::warning('PayU Wallet Webhook - Hash verification failed', [
                        'calculated_hash' => $calculatedHash,
                        'received_hash' => $receivedHash,
                    ]);
                    // Continue anyway - log the mismatch
                }
            }

            // Check if transaction already exists
            $existingTransaction = WalletTransaction::where('transaction_id', $transactionId)
                ->where('wallet_id', $wallet->id)
                ->first();

            if ($existingTransaction) {
                // Update existing transaction
                $existingTransaction->update([
                    'status' => $status === 'success' ? 'success' : ($status === 'failed' ? 'failed' : 'pending'),
                    'payu_response' => $request->all(),
                ]);

                Log::info('PayU Wallet Webhook - Transaction updated', [
                    'transaction_id' => $transactionId,
                    'wallet_id' => $wallet->id,
                ]);
            } else {
                // Create new transaction record (synced from PayU)
                $balanceBefore = (float) ($request->input('balance_before') ?? $wallet->balance);
                $balanceAfter = (float) ($request->input('balance_after') ?? $wallet->balance);

                WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'user_id' => $wallet->user_id,
                    'transaction_type' => $transactionType ?? 'credit',
                    'transaction_id' => $transactionId,
                    'amount' => (float) $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'description' => $request->input('description') ?? 'Transaction from PayU webhook',
                    'payu_response' => $request->all(),
                    'status' => $status === 'success' ? 'success' : ($status === 'failed' ? 'failed' : 'pending'),
                    'sync_source' => true,
                ]);

                Log::info('PayU Wallet Webhook - Transaction created', [
                    'transaction_id' => $transactionId,
                    'wallet_id' => $wallet->id,
                ]);
            }

            // Sync wallet balance
            $this->walletService->syncBalance($wallet);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook processed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('PayU Wallet Webhook Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Extract all PayU response fields from request (same as invoice payments).
     */
    protected function extractPayuResponseFields(Request $request): array
    {
        // PayU may send data via POST or GET (query string)
        $response = array_merge($request->query(), $request->post());

        // Extract all PayU response fields
        $payuFields = [
            // Payment identifiers
            'mihpayid' => $request->input('mihpayid') ?? $request->input('payuMoneyId') ?? $request->input('payuid'),
            'txnid' => $request->input('txnid'),
            'key' => $request->input('key'),

            // Payment status
            'status' => $request->input('status'),
            'unmappedstatus' => $request->input('unmappedstatus'),

            // Payment details
            'amount' => $request->input('amount'),
            'productinfo' => $request->input('productinfo'),
            'firstname' => $request->input('firstname'),
            'lastname' => $request->input('lastname'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),

            // Payment method details
            'mode' => $request->input('mode'), // CC, DC, NB, UPI, etc.
            'bankcode' => $request->input('bankcode'),
            'bank_ref_num' => $request->input('bank_ref_num'),
            'pg_type' => $request->input('pg_type'),
            'cardnum' => $request->input('cardnum'), // Masked card number
            'name_on_card' => $request->input('name_on_card'),
            'card_type' => $request->input('card_type'),
            'issuing_bank' => $request->input('issuing_bank'),
            'card_category' => $request->input('card_category'),

            // Error details (for failed payments)
            'error' => $request->input('error'),
            'error_code' => $request->input('error_code'),
            'error_Message' => $request->input('error_Message') ?? $request->input('error_message'),

            // Additional fields
            'udf1' => $request->input('udf1'),
            'udf2' => $request->input('udf2'),
            'udf3' => $request->input('udf3'),
            'udf4' => $request->input('udf4'),
            'udf5' => $request->input('udf5'),
            'hash' => $request->input('hash'),
            'field1' => $request->input('field1'),
            'field2' => $request->input('field2'),
            'field3' => $request->input('field3'),
            'field4' => $request->input('field4'),
            'field5' => $request->input('field5'),
            'field6' => $request->input('field6'),
            'field7' => $request->input('field7'),
            'field8' => $request->input('field8'),
            'field9' => $request->input('field9'),

            // Additional payment gateway fields
            'discount' => $request->input('discount'),
            'net_amount_debit' => $request->input('net_amount_debit'),
            'addedon' => $request->input('addedon'),
            'payment_source' => $request->input('payment_source'),
            'card_token' => $request->input('card_token'),
            'offer_key' => $request->input('offer_key'),
            'offer_type' => $request->input('offer_type'),
            'offer_availed' => $request->input('offer_available'),
            'failure_reason' => $request->input('failure_reason'),
            'retry' => $request->input('retry'),
        ];

        // Remove null values to keep response clean
        $payuFields = array_filter($payuFields, function ($value) {
            return $value !== null && $value !== '';
        });

        // Also include the complete raw response for reference
        $payuFields['raw_response'] = $response;

        return $payuFields;
    }

    /**
     * Calculate total billing cycle amount for all live applications of a user.
     */
    private function calculateTotalBillingCycleAmount(Registration $user): float
    {
        try {
            return (float) \App\Models\Invoice::query()
                ->whereHas('application', function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->where('application_type', 'IRINN');
                })
                ->where(function ($q) {
                    $q->where('payment_status', 'pending')
                        ->orWhere('payment_status', 'partial');
                })
                ->where('status', '!=', 'cancelled')
                ->get()
                ->sum(fn ($inv) => (float) ($inv->balance_amount ?? $inv->total_amount ?? 0));
        } catch (\Exception $e) {
            Log::error('Error calculating outstanding invoice total for wallet: '.$e->getMessage());

            return 0;
        }
    }
}
