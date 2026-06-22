<?php
require_once  '../PHPMailer/src/Exception.php';
require_once  '../PHPMailer/src/PHPMailer.php';
require_once  '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function send_activation_email($to, $subject, $body) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = '';
        $mail->Password = '';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('no-reply@yourdomain.com', 'Workout app');
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
