<?php

class SmsHelper
{
    private static string $apiKey = 'YOUR_ARKESEL_API_KEY';
    private static string $apiUrl = 'https://sms.arkesel.com/api/v2/sms/send';
    private static string $sender = 'YourSenderID'; // Should be approved by Arkesel

    public static function sendPasswordResetSMS(string $phone, string $token): bool
    {
        $resetLink = "https://yourdomain.com/reset-password?token=$token";
        $message = "Reset your password here: $resetLink";

        $payload = [
            "sender"     => self::$sender,
            "message"    => $message,
            "recipients" => [$phone]
        ];

        $headers = [
            "Authorization: Bearer " . self::$apiKey,
            "Content-Type: application/json"
        ];

        $ch = curl_init(self::$apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}
