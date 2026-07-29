<?php

require __DIR__ . '/vendor/autoload.php';

$resend = Resend::client('re_WvjrJbW9_8Gper4wbRAuodktYmHxwkUy1');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    $from_email=($_POST['from_email'])?$_POST['from_email']:'admin@worldproductionservice.com';

try {
$mail=$resend->emails->send([
  'from' => 'World Production Service <'.$from_email.'>',
  'to' =>$email,
  'subject' =>  $subject,
  'html' =>   $message
]);
if($mail){
   echo 'Successfully Sent'; 
}

} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$e->ErrorInfo}";
}
}
else{
   echo  'Unknow Request!';
}
