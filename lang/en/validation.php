<?php

return [
    'custom' => [
        'username' => [
            'required' => 'Enter your username',
            'regex'    => 'Only Latin letters, numbers, and the "_" symbol are allowed',
            'exists'   => 'No user with this username exists in the system'
        ],

        'toUsername' => [
            'required' => 'Enter the username',
            'regex'    => 'Only Latin letters, numbers, and the "_" symbol are allowed',
            'exists'   => 'No user with this username exists in the system',
            'not_self' => 'You cannot transfer funds to yourself',
        ],

        'login' => [
            'required' => 'Enter username or email',
            'regex'    => 'Only Latin letters are allowed.',
            'credentials' => 'Invalid login/email or password'
        ],

        'telegram' => [
            'required' => 'Enter your Telegram username',
            'regex'    => '@ + 5‑32 Latin letters, numbers or _',
        ],

        'firstName' => [
            'required' => 'Enter your first name',
        ],
        'lastName' => [
            'required' => 'Enter your last name',
        ],

        'email' => [
            'required' => 'Enter your email address',
            'email'    => 'Enter a valid email address.',
            'credentials' => 'Invalid login/email or password',
        ],

        'password' => [
            'required'  => 'Enter a password',
            'min'       => 'Password must be at least :min characters.',
            'regex'     => 'Password must contain at least one number, only Latin letters or symbols _ & @ * ^ % #.',
            'confirmed' => 'Passwords do not match.',
            'credentials' => 'Invalid login/email or password',
        ],

        'newPassword' => [
            'required'  => 'Enter a password',
            'min'       => 'Password must be at least :min characters.',
            'regex'     => 'Password must contain at least one number, only Latin letters or symbols _ & @ * ^ % #.',
            'confirmed' => 'Passwords do not match.',
        ],

        'passwordConfirm' => [
            'required'  => 'Repeat the password',
            'same'      => 'Passwords do not match.',
        ],

        'newPasswordConfirm' => [
            'required'  => 'Repeat the password',
            'same'      => 'Passwords do not match.',
        ],

        'depositAmount' => [
            'required' => 'Enter deposit amount',
            'numeric'  => 'Amount must be a number',
            'min'      => 'Amount must be at least :min',
        ],

        'amount' => [
            'required' => 'Enter deposit amount',
            'numeric'  => 'Amount must be a number',
            'min'      => 'Amount must be at least :min',
        ],

        'toPartnerAmount' => [
            'required' => 'Enter transfer amount',
            'numeric'  => 'Amount must be a number',
            'balance'  => 'Insufficient funds on partner balance',
        ],

        'toPackageAmount' => [
            'required' => 'Enter transfer amount',
            'numeric'  => 'Amount must be a number',
            'balance'  => 'Insufficient funds on partner balance',
        ],

        'withdrawAmount' => [
            'required' => 'Enter withdrawal amount',
            'numeric'  => 'Amount must be a number',
            'min'      => 'Amount must be at least :min',
        ],

        'withdrawPackageAmount' => [
            'required' => 'Enter withdrawal amount',
            'numeric'  => 'Amount must be a number',
            'min'      => 'Amount must be at least :min',
            'max_package_sum' => 'Amount cannot exceed package balance'
        ],

        'sbpPhone' => [
            'required_without' => 'Provide phone number',
            'string'   => 'Value must be a string',
            'regex'     => 'Invalid format',
            'max'      => 'Value cannot exceed :max characters',
        ],

        'bankName' => [
            'required_without' => 'Provide bank name',
            'string'   => 'Value must be a string',
            'max'      => 'Value cannot exceed :max characters',
        ],

        'transactionHash' => [
            'required' => 'Provide transaction hash or bank name',
            'string'   => 'Value must be a string',
            'max'      => 'Value cannot exceed :max characters',
        ],

        'recipientName' => [
            'required_without' => 'Provide full name',
            'string'   => 'Value must be a string',
            'max'      => 'Value cannot exceed :max characters',
        ],

        'address' => [
            'required_without_all' => 'Provide wallet address',
            'string'   => 'Value must be a string',
            'max'      => 'Value cannot exceed :max characters',
        ],
    ],

    'attributes' => [
        'username'        => 'username',
        'email'           => 'email',
        'password'        => 'password',
        'passwordConfirm' => 'password confirmation',
    ],
];
