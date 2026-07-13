<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reset Password E-Portal UIKA</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f6f8;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8;padding:40px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
          
          <!-- Header -->
          <tr>
            <td style="background:linear-gradient(135deg,#059669 0%,#047857 100%);padding:40px 48px;text-align:center;">
              <h1 style="margin:0;color:#ffffff;font-size:24px;font-weight:800;letter-spacing:-0.5px;">
                E-Portal UIKA
              </h1>
              <p style="margin:8px 0 0;color:#a7f3d0;font-size:14px;font-weight:500;">
                Universitas Ibn Khaldun Bogor
              </p>
            </td>
          </tr>

          <!-- Body -->
          <tr>
            <td style="padding:48px;">
              <!-- Icon -->
              <div style="text-align:center;margin-bottom:32px;">
                <div style="display:inline-block;background-color:#f0fdf4;border-radius:50%;padding:20px;">
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C13.1 2 14 2.9 14 4C14 5.1 13.1 6 12 6C10.9 6 10 5.1 10 4C10 2.9 10.9 2 12 2ZM21 9H15V22H13V16H11V22H9V9H3V7H21V9Z" fill="#059669"/>
                  </svg>
                </div>
              </div>

              <h2 style="margin:0 0 12px;color:#111827;font-size:22px;font-weight:800;text-align:center;">
                Reset Password Anda
              </h2>
              <p style="margin:0 0 32px;color:#6b7280;font-size:15px;line-height:1.6;text-align:center;">
                Kami menerima permintaan reset password untuk akun E-Portal UIKA terkait dengan email <strong style="color:#111827;">{{ $email }}</strong>.
              </p>

              <!-- Button -->
              <div style="text-align:center;margin-bottom:32px;">
                <a href="{{ $resetUrl }}" 
                   style="display:inline-block;background:linear-gradient(135deg,#059669 0%,#047857 100%);color:#ffffff;text-decoration:none;padding:16px 40px;border-radius:12px;font-size:16px;font-weight:700;letter-spacing:0.3px;box-shadow:0 4px 12px rgba(5,150,105,0.3);">
                  Reset Password Sekarang
                </a>
              </div>

              <!-- Link fallback -->
              <div style="background-color:#f9fafb;border-radius:12px;padding:20px;margin-bottom:32px;">
                <p style="margin:0 0 8px;color:#6b7280;font-size:13px;">
                  Jika tombol di atas tidak berfungsi, salin dan tempel link berikut ke browser Anda:
                </p>
                <p style="margin:0;word-break:break-all;">
                  <a href="{{ $resetUrl }}" style="color:#059669;font-size:13px;text-decoration:none;">
                    {{ $resetUrl }}
                  </a>
                </p>
              </div>

              <!-- Warning -->
              <div style="border-left:4px solid #f59e0b;background-color:#fffbeb;padding:16px 20px;border-radius:0 8px 8px 0;margin-bottom:32px;">
                <p style="margin:0;color:#92400e;font-size:13px;line-height:1.6;">
                  ⚠️ <strong>Link ini hanya berlaku selama 60 menit.</strong> Jika Anda tidak meminta reset password, abaikan email ini — akun Anda tetap aman.
                </p>
              </div>

              <hr style="border:none;border-top:1px solid #e5e7eb;margin:0 0 24px;">

              <p style="margin:0;color:#9ca3af;font-size:13px;text-align:center;line-height:1.6;">
                Email ini dikirim otomatis oleh sistem E-Portal UIKA.<br>
                Jangan balas email ini.
              </p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td style="background-color:#f9fafb;padding:24px 48px;text-align:center;border-top:1px solid #e5e7eb;">
              <p style="margin:0 0 4px;color:#6b7280;font-size:13px;font-weight:600;">
                E-Portal SSO — Universitas Ibn Khaldun Bogor
              </p>
              <p style="margin:0;color:#9ca3af;font-size:12px;">
                Jl. KH. Sholeh Iskandar KM.2, Kedung Badak, Kec. Tanah Sareal, Kota Bogor
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>