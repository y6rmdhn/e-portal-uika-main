<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $newPassword,
        public string $adminName,
    ) {}

    public function build(): self
    {
        return $this
            ->subject('Password Akun E-Portal Anda Telah Direset')
            ->html("
                <div style='font-family: sans-serif; max-width: 500px; margin: 0 auto;'>
                    <h2 style='color: #059669;'>Password Anda Telah Diubah</h2>
                    <p>Halo <strong>{$this->userName}</strong>,</p>
                    <p>Administrator <strong>{$this->adminName}</strong> telah mereset password akun E-Portal Anda.</p>
                    <div style='background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px; margin: 20px 0;'>
                        <p style='margin: 0; font-size: 14px; color: #166534;'>
                            Password baru Anda akan diberitahukan secara langsung oleh administrator.
                        </p>
                    </div>
                    <p>Jika Anda merasa tidak meminta perubahan ini, segera hubungi administrator kampus.</p>
                    <p style='color: #6b7280; font-size: 12px; margin-top: 30px;'>E-Portal Universitas Ibn Khaldun Bogor</p>
                </div>
            ");
    }
}
