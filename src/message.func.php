<?php
#############################
# Common Message functions  #
#############################

// Send Message
function sendMessage($message, $recipient = CHANGE_REQUESTS_TO, $subject = "Message from Customer Portal", $from = MAIL_FROM, $fromName = MAIL_FROM_NAME)
{
        $mail = new \PHPMailer\PHPMailer\PHPMailer();

        //$mail->SMTPDebug = 3;                             // Enable verbose debug output

        $mail->isSMTP();                                    // Set mailer to use SMTP
        $mail->Host = SMTP_HOST;                          // Specify main and backup SMTP servers
        $mail->SMTPAuth = SMTP_AUTH;                            // Enable SMTP authentication
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;                                   // TCP port to connect to
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        
        $mail->CharSet = 'utf-8';
        
        $mail->From = $from;
        $mail->FromName = $fromName;
        
        $addresses = explode(',', $recipient);
        foreach ($addresses as $address) {
            $mail->AddAddress(trim($address));
        }

        $mail->Subject = mb_substr(iconv_mime_encode("", $subject, array("input-charset" => "UTF-8", "output-charset" => "UTF-8")), 2);
        $mail->Body    = $message;

        return $mail->send();
}

?>