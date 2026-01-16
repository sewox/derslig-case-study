<?php

return [
    'auth' => [
        'registered' => 'Kullanıcı başarıyla kaydedildi',
        'login_success' => 'Giriş başarılı',
        'logout_success' => 'Çıkış başarılı',
        'invalid_credentials' => 'Geçersiz kimlik bilgileri',
        'validation_error' => 'Verilen bilgiler geçersiz.',
        'email_taken' => 'Bu e-posta adresi ile daha önce kayıt olunmuş.',
    ],
    'transaction' => [
        'deposit_success' => 'Para yatırma işlemi başarılı',
        'withdraw_success' => 'Para çekme işlemi başarılı',
        'transfer_success' => 'Transfer işlemi başarılı',
        'daily_limit_exceeded' => 'Günlük :limit TRY transfer limiti aşıldı.',
        'transaction_exceeds_limit' => 'İşlem günlük limiti aşıyor.',
        'cannot_transfer_self' => 'Kendinize transfer yapamazsınız',
        'insufficient_balance' => 'Yetersiz bakiye',
    ],
    'wallet' => [
        'not_found' => 'Cüzdan bulunamadı',
        'not_found_or_access' => 'Cüzdan bulunamadı veya kullanıcıya ait değil',
        'source_not_found' => 'Kaynak cüzdan bulunamadı',
        'target_user_not_found' => 'Hedef kullanıcı bulunamadı',
        'target_no_currency' => 'Hedef kullanıcının bu para birimi için cüzdanı yok',
    ],
    'error' => [
        'duplicate_request' => 'Çok fazla istek gönderildi. Lütfen biraz bekleyin.',
    ],
