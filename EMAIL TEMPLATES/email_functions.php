<?php
/**
 * Email Template Functions
 * 
 * This file contains functions for generating email templates with consistent design
 */

/**
 * Get common email template header
 * 
 * @param string $title The title to display in the header
 * @return string HTML for the email header
 */
function getEmailHeader($title) {
    // Use a Base64 encoded image that has better visibility with a colored fill
    // The logo is a blue/green pest control icon with better contrast
    $base64Logo = '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';
    
    return "
    <div class='header'>
        <div class='logo-container'>
            <img src='data:image/png;base64,{$base64Logo}' alt='PESTCOZAM Logo' class='logo' style='background-color: transparent; border-radius: 0; padding: 0;'>
            <span class='brand-name'>PESTCOZAM</span>
        </div>
        <h2 class='title'>" . htmlspecialchars($title) . "</h2>
    </div>";
}

/**
 * Get common email template footer
 * 
 * @return string HTML for the email footer
 */
function getEmailFooter() {
    return "
    <div class='footer'>
        <p>© " . date('Y') . " PESTCOZAM. All rights reserved.</p>
        <p>This is an automated message. Please do not reply to this email.</p>
    </div>";
}

/**
 * Get common email CSS styles
 * 
 * @return string CSS styles for emails
 */
function getEmailStyles() {
    return "
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 0; background-color: #ffffff; }
        .header { background-color: #0C3B6F; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .logo-container { display: flex; align-items: center; justify-content: center; margin-bottom: 10px; }
        .logo { width: 40px; height: 40px; margin-right: 10px; }
        .brand-name { font-size: 24px; font-weight: bold; margin: 0; }
        .title { margin: 0; font-size: 20px; }
        .content { padding: 20px; border: 1px solid #e0e0e0; border-top: none; }
        .receipt-row { display: flex; justify-content: space-between; margin: 12px 0; padding: 8px 0; border-bottom: 1px dashed #e0e0e0; }
        .label { font-weight: 600; color: #444; }
        .button { 
            display: inline-block; 
            background-color: #0C3B6F; 
            color: #FFFFFF !important; 
            padding: 12px 25px; 
            text-decoration: none; 
            border-radius: 5px; 
            margin: 20px 0; 
            font-weight: bold; 
            text-align: center;
        }
        .footer { font-size: 12px; text-align: center; margin-top: 20px; color: #666; padding: 15px; background-color: #f5f5f5; border-radius: 0 0 5px 5px; }
        .note { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #0C3B6F; margin: 15px 0; font-size: 14px; }
        .important { color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 4px; margin: 15px 0; }
        .success { color: #155724; background-color: #d4edda; padding: 10px; border-radius: 4px; margin: 15px 0; }
        ul { padding-left: 20px; }
        li { margin-bottom: 5px; }
    </style>";
}

/**
 * Get appointment confirmation email template
 * 
 * @param array $data Data for the appointment email
 * @return string Complete HTML email template
 */
function getAppointmentEmailTemplate($data) {
    $title = $data['title'] ?? 'Appointment Confirmation';
    $clientName = $data['clientName'] ?? '';
    $serviceName = $data['serviceName'] ?? '';
    $appointmentDate = $data['appointmentDate'] ?? '';
    $appointmentTime = $data['appointmentTime'] ?? '';
    $location = $data['location'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $price = $data['price'] ?? '0.00';
    
    return "
    <html>
    <head>
        " . getEmailStyles() . "
    </head>
    <body>
        <div class='container'>
            " . getEmailHeader($title) . "
            <div class='content'>
                <p>Dear " . htmlspecialchars($clientName) . ",</p>
                <p>Your appointment has been successfully booked with PESTCOZAM. Here are your appointment details:</p>
                
                <ul>
                    <li><strong>Client Name:</strong> " . htmlspecialchars($clientName) . "</li>
                    <li><strong>Service:</strong> " . htmlspecialchars($serviceName) . "</li>
                    <li><strong>Date:</strong> " . htmlspecialchars($appointmentDate) . "</li>
                    <li><strong>Time:</strong> " . htmlspecialchars($appointmentTime) . "</li>
                    <li><strong>Location:</strong> " . htmlspecialchars($location) . "</li>
                    <li><strong>Email:</strong> " . htmlspecialchars($email) . "</li>
                    <li><strong>Phone:</strong> " . htmlspecialchars($phone) . "</li>
                    <li><strong>Starting Price:</strong> ₱" . number_format((float)$price, 2) . "</li>
                </ul>
                
                <div class='note'>
                    <p><strong>Important:</strong> Please note that a technician will be assigned to your appointment soon. You will be contacted once a technician has been assigned to your service.</p>
                </div>
                
                <p>Please note that the final price will be determined after ocular inspection.</p>
                <p>If you need to make any changes to your appointment or have any questions, please contact us at 0905-177-5662 or reply to this email.</p>
                <p>Thank you for choosing PESTCOZAM for your pest control needs!</p>
            </div>
            " . getEmailFooter() . "
        </div>
    </body>
    </html>";
}

/**
 * Get general notification email template
 * 
 * @param array $data Data for the notification email
 * @return string Complete HTML email template
 */
function getNotificationEmailTemplate($data) {
    $title = $data['title'] ?? 'Notification';
    $greeting = $data['greeting'] ?? 'Hello';
    $message = $data['message'] ?? '';
    $callToAction = $data['callToAction'] ?? null;
    $callToActionUrl = $data['callToActionUrl'] ?? '#';
    $callToActionText = $data['callToActionText'] ?? 'Click Here';
    
    $buttonHtml = '';
    if ($callToAction) {
        $buttonHtml = "
        <div style='text-align: center;'>
            <a href='" . $callToActionUrl . "' class='button'>" . htmlspecialchars($callToActionText) . "</a>
        </div>";
    }
    
    return "
    <html>
    <head>
        " . getEmailStyles() . "
    </head>
    <body>
        <div class='container'>
            " . getEmailHeader($title) . "
            <div class='content'>
                <p>" . htmlspecialchars($greeting) . ",</p>
                <p>" . nl2br(htmlspecialchars($message)) . "</p>
                
                " . $buttonHtml . "
                
                <p>Thank you,<br>PESTCOZAM Support Team</p>
            </div>
            " . getEmailFooter() . "
        </div>
    </body>
    </html>";
}
?>
