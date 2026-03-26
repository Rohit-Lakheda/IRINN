<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'idfy' => [
        // 'account_id' => env('IDFY_ACCOUNT_ID', '038bb27f4ff8/cef38574-71bd-45b9-9faf-e1041c23ad46'),
        'account_id' => env('IDFY_ACCOUNT_ID', '75e88798ac23/cc12111f-b8e4-4aa4-9409-d9c9b5d0a2c1'),
        'api_key' => env('IDFY_API_KEY', 'd62d5ae7-edf0-479b-9f05-00f503df7032'),
        // 'base_url' => env('IDFY_BASE_URL', 'https://eve.idfy.com'),
        'base_url' => env('IDFY_BASE_URL', 'https://api.idfy.com'),
    ],

    // 'payu' => [
    //     'merchant_id' => env('PAYU_MERCHANT_ID', '8092319'),
    //     'merchant_key' => env('PAYU_MERCHANT_KEY', 'PxBkYs9d'),
    //     'salt' => env('PAYU_SALT', 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQC7sO9QIdd1hIxu9jYk71aXRKxI4yHdMvLUF93yN7pHVYYyPU+/Gz9JvfUwDBcf53XpXPj4je5v5LrqRrAw0vdIkDzyhtxQYkT2FrdUN8bCO6eJmY6Qwxdyh9pmPY9m5PmE18WRyqn5PfgPcPlV/EtcU0eP8o6U3Jmsy2M4leUH7/YLLZSGDn7CpHEqjQSFsJsbFhCqdXoyr66UqD9CTeLCAPkCK8TFOiC6uU7PolDxBzEnphLf2Ki85i9IEzDusKpkkiNXeEElCBXJ7CEZsQb5vJDYnmjTjTn4uE1EzOTfDZ/qktVLS6pL4UJ2iUb0iCK0l50dKkaQ1zYo5m5SNVrNAgMBAAECggEBAK+0q9QwWivBslYOWBGrnTxvJXn2Z7VUBM9YqIYgdVpiTgaqLwWQ1GaMVgRszRko7dFRICJPfG9ziSfYlQif9q8PmH7SkV0PIc/e5DELJh6fCfBeuH/8ou6tD1+3MY/5uz0JYgNh3k1eHeMWt9QvI72CmPPA9omVGqgQnwpSCN6rwxByzuPJoaazyYfXNgJuf3QNi/1BE/JJIn6bFt5+zq1DMgFfM1IqQa0wW1C6x2f9/JJBPmgH3Ln8ocdcgnXh7ifN9wb3YBRllxov5RZaWUaMn5s2x0DcYFFFTrA5on0gArI8TbZ23x1jBCTwbAoazj3KmIZEIahUuppy2dd1cWECgYEA3e8rCVPTWauMcsMyAfxeu5L7qcHu9jmKi8d8j/Is3vXrKb6XLfW+t9ZQET+ZzLMIMAaXjV1fE1u4PyY7Pei45/YKL3sLx5wUJq3XOd1xi+rlv89kqo3e3S6bjLylSBzyaoj7s22L6JzdnB2QE2e/P1E0z2/3X+srcBeBDyX40zsCgYEA2IAyFMyVUWtCHqsMo9PGO2LLp9s39vUNJflG975UbwAeW7Z5KeHRhF4Cl2MCkvwt61SKIQ977widFN5A/f7n9A/x8U2xT4aXr/QYYMABdcet5iIzjrSf+GOKvj0lXU5L8hvcf59Oo1KKItN2k61WDj9DmV1ddlWYqt2h6EJwGZcCgYEAuBBuLxQ4y7v9hgjh5se6gfNLieVwHQJoJ7nRU0lVca2f1kVd+R5BiRLT4RpQoncxqTMuam4wNkvxqV458ASdprRmii6Q/II0LEgtoq5IR/UPi1+ka9eyKNtI4xZqNj7bxwPJTWzjho7jNWFHZvC6qvbcx9Zi4kiXBCZaQYgKsJUCgYEAo755ok+FU5oa3RUjrzi5wiqbu23K6yY19pWNvkyekYF2dIkTJMEddM6hiRwiU0cV99ntyslqQ8SxAZqDb1d+2ZHBvG61f19dlH+6fFpcAFewx9DwS6uDHhszUWTvwJ06RXgbEg9MK6x+u45SLak43/erSfBaguiEWh7cmMy++isCgYAYQ73X1UZYzrYnAnhT9ppaI/wCzYoXQdF85DbkZPKPNoVFcfTWzJo4foBE/TfV2IsBdr8jAYJuu+AP5JSGm6Gkuu0Tw4Ig/+MHTHF7F48zQOsuHhN3p6yBCMGIGqSJhCoXu4QRSdB5SiZKeB3345xi4rClE5ZxAOmHggscy5UFsg=='),
    //     'test_url' => env('PAYU_TEST_URL', 'https://test.payu.in/_payment'),
    //     'live_url' => env('PAYU_LIVE_URL', 'https://secure.payu.in/_payment'),
    //     'mode' => env('PAYU_MODE', 'live'),
    //     'service_provider' => env('PAYU_SERVICE_PROVIDER', 'payu_paisa'),
    //     'webhook_url' => env('PAYU_WEBHOOK_URL', null), // Will be auto-generated if not set
    // ],
    'payu' => [
        'merchant_id' => env('PAYU_MERCHANT_ID', '8092319'),
        'merchant_key' => env('PAYU_MERCHANT_KEY', '5BEKf4'),
        'salt' => env('PAYU_SALT', 'PxBkYs9d'),
        'test_url' => env('PAYU_TEST_URL', 'https://test.payu.in/_payment'),
        'live_url' => env('PAYU_LIVE_URL', 'https://secure.payu.in/_payment'),
        'mode' => env('PAYU_MODE', 'live'),
        'service_provider' => env('PAYU_SERVICE_PROVIDER', 'payu_paisa'),
        'webhook_url' => env('PAYU_WEBHOOK_URL', null), // Will be auto-generated if not set
        'wallet' => [
            'api_base_url_test' => env('PAYU_WALLET_API_BASE_URL_TEST', 'https://test.payu.in/merchant/'),
            'api_base_url_live' => env('PAYU_WALLET_API_BASE_URL_LIVE', 'https://secure.payu.in/merchant/'),
            'create_wallet_endpoint' => env('PAYU_WALLET_CREATE_ENDPOINT', 'postservice.php?form=2'),
            'add_money_endpoint' => env('PAYU_WALLET_ADD_MONEY_ENDPOINT', 'postservice.php?form=2'),
            'debit_wallet_endpoint' => env('PAYU_WALLET_DEBIT_ENDPOINT', 'postservice.php?form=2'),
            'balance_inquiry_endpoint' => env('PAYU_WALLET_BALANCE_INQUIRY_ENDPOINT', 'postservice.php?form=2'),
            'transaction_history_endpoint' => env('PAYU_WALLET_TRANSACTION_HISTORY_ENDPOINT', 'postservice.php?form=2'),
        ],
    ],

];
