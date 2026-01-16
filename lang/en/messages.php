<?php

return [
    'auth' => [
        'registered' => 'User registered successfully',
        'login_success' => 'Login successful',
        'logout_success' => 'Logged out successfully',
        'invalid_credentials' => 'Invalid credentials',
        'validation_error' => 'The given data was invalid.',
        'email_taken' => 'The email has already been taken.',
    ],
    'transaction' => [
        'deposit_success' => 'Deposit successful',
        'withdraw_success' => 'Withdrawal successful',
        'transfer_success' => 'Transfer successful',
        'daily_limit_exceeded' => 'Daily transfer limit of :limit TRY exceeded.',
        'transaction_exceeds_limit' => 'Transaction exceeds daily limit.',
        'cannot_transfer_self' => 'Cannot transfer to yourself',
        'insufficient_balance' => 'Insufficient Balance',
    ],
    'wallet' => [
        'not_found' => 'Wallet not found',
        'not_found_or_access' => 'Wallet not found or does not belong to user',
        'source_not_found' => 'Source wallet not found',
        'target_user_not_found' => 'Target user not found',
        'target_no_currency' => 'Target user does not have a wallet for this currency',
    ],
    'error' => [
        'duplicate_request' => 'Too many requests. Please wait a moment.',
    ],
