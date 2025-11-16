<?php
// This file now ONLY defines the function, making it safe to 'require' in other pages.

/**
 * Sends an email using the SendGrid API (via cURL).
 *
 * @param string $apiKey Your SendGrid API Key.
 * @param string $to The recipient's email address.
 * @param string $subject The subject line of the email.
 * @param string $htmlContent The HTML content for the email.
 * @return bool true on success, false on failure.
 */

function smtp_mailer($to, $subject, $htmlContent) {
    
    // --- IMPORTANT ---
    // 1. Put your NEW, SECRET SendGrid API key here.
    // 2. Make sure the $from_email is a "Verified Sender" in SendGrid.
    
    $apiKey = "SENDGRID_API";
    $from_email = "sazim87@student.sust.edu"; // The email you verified
    $from_name = "SUSTAINABLE BANK LTD";

    $data = [
        'personalizations' => [
            [
                'to' => [['email' => $to]],
                'subject' => $subject
            ]
        ],
        'from' => [
            'email' => $from_email,
            'name' => $from_name
        ],
        'content' => [
            [
                'type' => 'text/html',
                'value' => $htmlContent
            ]
        ]
    ];

    $jsonData = json_encode($data);

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://api.sendgrid.com/v3/mail/send");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 202) { // 202 Accepted
        return true; // Success
    } else {
        // You can log the error if you want
        // error_log("SendGrid Error: " . $response);
        return false; // Failure
    }
}
?>

