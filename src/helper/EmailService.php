<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService
{
    private $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true); // Enable exceptions
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host       = $_ENV['MAIL_HOST'];   
            $this->mail->SMTPAuth   = true;
            $this->mail->Username   = $_ENV['MAIL_USERNAME'];   
            $this->mail->Password   = $_ENV['MAIL_PASSWORD'];                  
            $this->mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
            $this->mail->Port       = $_ENV['MAIL_PORT'];
            // Recipients
            $this->mail->setFrom('no-reply@yourdomain.com', 'Hotel Management System');
            $this->mail->isHTML(true);
        } catch (Exception $e) {
            error_log("PHPMailer initialization error: {$e->getMessage()}");
            error_log("Mailer Error: " . $this->mail->ErrorInfo);
            throw new Exception("Failed to initialize email service: " . $e->getMessage());
        }
    }

    /**
     * Sends an OTP to a specified email address.
     *
     * @param string $toEmail The recipient's email address.
     * @param string $otpCode The 6-digit OTP to send.
     * @return bool True on success, false on failure.
     */
    public function sendOtpEmail(string $toEmail, string $otpCode): bool
    {
        try {
            $this->mail->addAddress($toEmail);     // Add a recipient

            $this->mail->Subject = 'Your One-Time Password (OTP) for Password Reset';
            $this->mail->Body    = "
                <p>Dear User,</p>
                <p>Your One-Time Password (OTP) for password reset is: <strong>{$otpCode}</strong></p>
                <p>This OTP is valid for a limited time. Do not share this code with anyone.</p>
                <p>If you did not request a password reset, please ignore this email.</p>
                <p>Thank you,</p>
                <p>The Hotel Management System Team</p>
            ";
            $this->mail->AltBody = "Your One-Time Password (OTP) for password reset is: {$otpCode}. This OTP is valid for a limited time. Do not share this code with anyone. If you did not request a password reset, please ignore this email.";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Failed to send OTP email to {$toEmail}: {$this->mail->ErrorInfo}");
            echo "Mailer Error: {$this->mail->ErrorInfo}";
            return false;
        }
    }


/**
 * Generate a modern HTML email template for OTP verification
 * 
 * @param string $otp The OTP code to display in the email
 * @param string $userName The recipient's name (optional)
 * @return string HTML content for the email
 */
function generateOtpEmailTemplate(string $otp): string
{
    // Format OTP with spaces for better readability
    $formattedOtp = chunk_split($otp, 1, ' ');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
        }
        
        .email-header {
            background: linear-gradient(135deg, #6366F1 0%, #8B5CF6 100%);
            padding: 30px;
            text-align: center;
        }
        
        .email-header h1 {
            color: white;
            font-size: 26px;
            margin: 0;
            font-weight: 600;
        }
        
        .email-body {
            padding: 30px;
            color: #4B5563;
        }
        
        .greeting {
            font-size: 20px;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 15px;
        }
        
        .message {
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        .otp-container {
            background-color: #F3F4F6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 25px 0;
        }
        
        .otp-code {
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 5px;
            color: #4F46E5;
            padding: 10px 0;
        }
        
        .expiry {
            font-size: 14px;
            color: #6B7280;
            margin-top: 10px;
        }
        
        .note {
            background-color: #FEF9C3;
            padding: 15px;
            border-left: 4px solid #FBBF24;
            margin: 25px 0;
            border-radius: 4px;
        }
        
        .email-footer {
            text-align: center;
            padding: 20px 30px;
            background-color: #F9FAFB;
            font-size: 14px;
            color: #6B7280;
        }
        
        .footer-links {
            margin-top: 15px;
        }
        
        .footer-links a {
            color: #6366F1;
            text-decoration: none;
            margin: 0 10px;
        }
        
        .support {
            margin-top: 15px;
        }
        
        @media only screen and (max-width: 480px) {
            .email-header {
                padding: 20px;
            }
            
            .email-header h1 {
                font-size: 22px;
            }
            
            .email-body {
                padding: 20px;
            }
            
            .otp-code {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Verification Code</h1>
        </div>
        
        <div class="email-body">
            <div class="greeting">Hello!</div>
            
            <div class="message">
                You requested a verification code to reset your password. Please use the code below to complete the process:
            </div>
            
            <div class="otp-container">
                <div class="otp-code">{$formattedOtp}</div>
                <div class="expiry">This code will expire in 10 minutes</div>
            </div>
            
            <div class="note">
                If you didn't request this code, please ignore this email or contact support if you believe this is suspicious.
            </div>
            
            <div class="message">
                Thanks,<br>
                The Hotel Management System Team
            </div>
        </div>
        
        <div class="email-footer">
            <div>© 2024 Hotel Management System. All rights reserved.</div>
            
            <div class="footer-links">
                <a href="#">Privacy Policy</a> • 
                <a href="#">Terms of Service</a>
            </div>
            
            <div class="support">
                Need help? Contact our support team at <a href="mailto:support@example.com">support@example.com</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}

}


