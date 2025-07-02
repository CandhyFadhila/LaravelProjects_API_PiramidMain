<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title></title>
        <style>
            body {
                margin: 20;
                padding: 0;
                font-family: "Arial", Arial, sans-serif;
                color: #333;
                background-color: #fff;
            }

            .container {
                margin: 0 auto;
                width: 100%;
                max-width: 600px;
                padding: 0 0px;
                padding-bottom: 10px;
                border-radius: 5px;
                line-height: 1.8;
            }

            .header {
                border-bottom: 1px solid #eee;
            }

            .header a {
                font-size: 1.4em;
                color: #000;
                text-decoration: none;
                font-weight: 600;
            }

            .content {
                min-width: 700px;
                overflow: auto;
                line-height: 2;
            }

            .otp {
                background: linear-gradient(
                    to right,
                    #00bc69 0,
                    #00bc88 50%,
                    #00bca8 100%
                );
                margin: 0 auto;
                width: max-content;
                padding: 0 20px;
                color: #fff;
                border-radius: 4px;
            }

            .footer {
                color: #aaa;
                font-size: 0.8em;
                line-height: 1;
                font-weight: 300;
            }

            .email-info {
                color: #666666;
                font-weight: 400;
                font-size: 13px;
                line-height: 18px;
                padding-bottom: 6px;
            }

            .email-info a {
                text-decoration: none;
                color: #00bc69;
            }
        </style>
    </head>

    <body>
        <!--Subject: Login Verification Required for Your [App Name] Account-->
        <div class="container" style="margin-top: 50px">
            <strong>Kepada {{ $name }},</strong>
            <p>
                Kami menerima permintaan untuk mengubah kata sandi akun <b>Piramid</b> Anda. 
                Demi keamanan, harap verifikasi permintaan ini dengan memasukkan Kode OTP (One-Time Password) berikut kedalam sistem <b>Piramid</b>:
            </p>
            <h2 class="otp">{{ $otp_code }}</h2>
            <p style="font-size: 0.9em">
                <strong>Kode OTP ini berlaku selama 10 menit.</strong><br /><br />
                Jika Anda tidak mengajukan permintaan ini, abaikan email ini dan pastikan akun Anda tetap aman. 
                <strong>Jangan pernah membagikan kode OTP kepada siapa pun untuk menghindari penyalahgunaan akun Anda.</strong><br />
                <br /><br />
                <strong>Terima kasih telah menggunakan Piramid.</strong><br /><br />
                Salam hormat,<br />
                <strong>Piramid</strong>
            </p>

            <hr style="border: none; border-top: 0.5px solid #131111" />
            <div class="footer">
                <p>Email ini tidak dapat menerima balasan.</p>
                <p>
                    Untuk informasi lebih lanjut tentang Piramid dan akun Anda, silahkan hubungi admin atau pengelola Piramid melalui email atau whatsapp.
                </p>
            </div>
        </div>
        <div style="text-align: center; margin-bottom: 50px">
            <div class="email-info">
                &copy; {{ date('Y') }} {{ ENV('COMPANY_NAME') }}. Seluruh hak cipta dilindungi.
            </div>
        </div>
    </body>
    <!--    This template is made Redwan one from Ocoxe. -->
    <!-- https://www.ocoxe.com -->
</html>
