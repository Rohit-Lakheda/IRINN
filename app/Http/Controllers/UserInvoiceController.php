<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Registration;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserInvoiceController extends Controller
{
    /**
     * Display list of user's invoices.
     */
    public function index(Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            // Invoice statistics: only active invoices (exclude cancelled and those with credit note)
            $baseQuery = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->activeForTotals();

            $totalInvoices = (clone $baseQuery)->count();
            $pendingInvoices = (clone $baseQuery)->where(function ($q) {
                $q->where('status', 'pending')
                    ->orWhere('payment_status', 'partial');
            })->count();
            $paidInvoices = (clone $baseQuery)->where('status', 'paid')->count();

            // Get filter type from request (all, pending, paid)
            $filterType = $request->get('filter', 'all');

            // Build query: show all invoices (including cancelled/credit note) when filter is 'all'
            $query = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->with(['application', 'generatedBy']);

            if ($filterType === 'pending') {
                $query->activeForTotals()->where(function ($q) {
                    $q->where('status', 'pending')
                        ->orWhere('payment_status', 'partial');
                });
            } elseif ($filterType === 'paid') {
                $query->activeForTotals()->where('status', 'paid');
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('billing_period', 'like', "%{$search}%");
                });
            }

            $invoices = $query->latest('invoice_date')->paginate(15)->withQueryString();

            // Get wallet information for pay with advance button
            $wallet = $user->wallet;
            $walletBalance = $wallet ? (float) $wallet->balance : 0;

            return view('user.invoices.index', compact('user', 'invoices', 'totalInvoices', 'pendingInvoices', 'paidInvoices', 'filterType', 'wallet', 'walletBalance'));
        } catch (Exception $e) {
            Log::error('Error loading user invoices: '.$e->getMessage());

            return redirect()->route('user.dashboard')
                ->with('error', 'Unable to load invoices.');
        }
    }

    /**
     * Download invoice PDF or credit note PDF.
     * Query parameter 'type' can be 'invoice' or 'credit_note' to specify which PDF to download.
     */
    public function download($id, Request $request)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $invoice = Invoice::with('application')
                ->whereHas('application', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->findOrFail($id);

            $application = $invoice->application;
            $downloadType = $request->query('type', 'auto'); // 'invoice', 'credit_note', or 'auto'

            if ($application->application_type === 'IX') {
                // If credit note exists and type is 'credit_note' or 'auto', serve credit note
                if (($downloadType === 'credit_note' || $downloadType === 'auto') && $invoice->hasCreditNote()) {
                    // Ensure credit note PDF exists (generate if missing)
                    app(AdminController::class)->ensureCreditNotePdfExists($application, $invoice);

                    if ($invoice->credit_note_pdf_path && Storage::disk('public')->exists($invoice->credit_note_pdf_path)) {
                        $filePath = Storage::disk('public')->path($invoice->credit_note_pdf_path);
                        // Credit note filename: invoice_number + "C.pdf" (e.g., NIXIEX2526-2292C.pdf)
                        $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'C.pdf';

                        return response()->download($filePath, $safeFilename);
                    }

                    return back()->with('error', 'Credit note PDF not found and could not be generated.');
                }

                // Serve invoice PDF (original or cancelled version)
                if ($downloadType === 'invoice' || ($downloadType === 'auto' && ! $invoice->hasCreditNote())) {
                    app(AdminController::class)->ensureInvoicePdfExists($application, $invoice);

                    if ($invoice->pdf_path && Storage::disk('public')->exists($invoice->pdf_path)) {
                        $filePath = Storage::disk('public')->path($invoice->pdf_path);
                        $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'_invoice.pdf';

                        return response()->download($filePath, $safeFilename);
                    }

                    return redirect()->route('user.invoices.index')
                        ->with('error', 'Invoice PDF not found. It may not have been generated yet.');
                }

                return redirect()->route('user.invoices.index')
                    ->with('error', 'Invalid download type specified.');
            }

            $safeFilename = str_replace(['/', '\\'], '-', $invoice->invoice_number).'_invoice.pdf';

            // For IRIN applications, serve from application_data pdfs
            $appData = $application->application_data ?? [];
            $pdfs = $appData['pdfs'] ?? [];

            if (isset($pdfs['invoice_pdf']) && Storage::disk('public')->exists($pdfs['invoice_pdf'])) {
                $filePath = Storage::disk('public')->path($pdfs['invoice_pdf']);

                return response()->download($filePath, $safeFilename);
            }

            return redirect()->route('user.invoices.index')
                ->with('error', 'Invoice PDF not found. Please contact support.');
        } catch (Exception $e) {
            Log::error('Error downloading invoice PDF: '.$e->getMessage());

            return redirect()->route('user.invoices.index')
                ->with('error', 'Unable to download invoice PDF.');
        }
    }

    /**
     * Update TDS amount for an invoice (only when payment status is pending).
     */
    public function updateTdsAmount(Request $request, $id)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $invoice = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->findOrFail($id);

            // Check if payment status is pending
            if ($invoice->payment_status !== 'pending') {
                return back()->with('error', 'TDS amount can only be updated when payment status is pending.');
            }

            $baseAmount = (float) $invoice->amount;
            $maxTdsAmount = ($baseAmount * 10) / 100;

            $validated = $request->validate([
                'tds_amount' => [
                    'required',
                    'numeric',
                    'min:0',
                    function ($attribute, $value, $fail) use ($maxTdsAmount) {
                        $tdsAmount = (float) $value;
                        if ($tdsAmount > $maxTdsAmount) {
                            $fail('The TDS amount cannot exceed 10% of the base amount (₹'.number_format($maxTdsAmount, 2).').');
                        }
                    },
                ],
            ]);

            $tdsAmount = (float) $validated['tds_amount'];
            $tdsPercentage = $baseAmount > 0 ? ($tdsAmount / $baseAmount) * 100 : 0;

            // Recalculate total amount with new TDS
            $gstAmount = (float) ($invoice->gst_amount ?? 0);
            $newTotalAmount = round($baseAmount + $gstAmount - $tdsAmount, 2);

            // Update invoice
            $invoice->update([
                'tds_percentage' => $tdsPercentage,
                'tds_amount' => $tdsAmount,
                'total_amount' => $newTotalAmount,
                'balance_amount' => max(0, $newTotalAmount - (float) ($invoice->paid_amount ?? 0)),
                'tds_updated_at' => now(),
            ]);

            return back()->with('success', 'TDS amount updated successfully.');
        } catch (Exception $e) {
            Log::error('Error updating TDS amount: '.$e->getMessage());

            return back()->with('error', 'Unable to update TDS amount. Please try again.');
        }
    }

    /**
     * Upload TDS certificate for an invoice.
     */
    public function uploadTdsCertificate(Request $request, $id)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $invoice = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->findOrFail($id);

            // Check if certificate already exists
            if ($invoice->tds_certificate_path) {
                return back()->with('error', 'TDS certificate has already been uploaded for this invoice.');
            }

            $validated = $request->validate([
                'tds_certificate' => 'required|file|mimes:pdf|max:10240', // 10MB max, PDF only
            ]);

            // Handle TDS certificate upload
            $file = $request->file('tds_certificate');
            $userName = $invoice->application->user->fullname ?? 'user';
            // Sanitize filename
            $sanitizedName = preg_replace('/[^a-zA-Z0-9_]/', '_', $userName);
            $sanitizedName = str_replace(' ', '_', $sanitizedName);

            // Generate filename: name_tds_timestamp.pdf
            $timestamp = now()->format('YmdHis');
            $filename = strtolower($sanitizedName).'_tds_'.$timestamp.'.pdf';

            // Create directory if it doesn't exist
            $directory = public_path('tds_certificate');
            if (! is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            // Store file
            $file->move($directory, $filename);
            $tdsCertificatePath = 'tds_certificate/'.$filename;

            // Update invoice
            $invoice->update([
                'tds_certificate_path' => $tdsCertificatePath,
            ]);

            return back()->with('success', 'TDS certificate uploaded successfully.');
        } catch (Exception $e) {
            Log::error('Error uploading TDS certificate: '.$e->getMessage());

            return back()->with('error', 'Unable to upload TDS certificate. Please try again.');
        }
    }

    /**
     * View/Download TDS certificate.
     */
    public function viewTdsCertificate($id)
    {
        try {
            $userId = session('user_id');
            $user = Registration::find($userId);

            if (! $user) {
                return redirect()->route('login.index')
                    ->with('error', 'User session expired. Please login again.');
            }

            $invoice = Invoice::whereHas('application', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
                ->findOrFail($id);

            if (! $invoice->tds_certificate_path) {
                return back()->with('error', 'TDS certificate not found.');
            }

            $filePath = public_path($invoice->tds_certificate_path);

            if (! file_exists($filePath)) {
                return back()->with('error', 'TDS certificate file not found.');
            }

            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
            ]);
        } catch (Exception $e) {
            Log::error('Error viewing TDS certificate: '.$e->getMessage());

            return back()->with('error', 'Unable to view TDS certificate. Please try again.');
        }
    }
}
