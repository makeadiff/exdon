<?php
require_once "Mail.php";
require_once "Mail/mime.php";
error_reporting(E_ERROR | E_PARSE);

class Email
{
    public $to = '';
    public $subject = '';
    public $html = '';
    public $from = '';
    public $images = array();

    private $host = 'smtp.gmail.com';
    private $username = 'noreply@makeadiff.in';
    private $password = 'noreplygonemad';

    function send() {

        $headers = array ('From' => $this->from,
            'To' => $this->to,
            'Subject' => $this->subject);

        $mime = new Mail_mime(array('eol' => "\n"));
        $mime->setHTMLBody($this->html);

        foreach($this->images as $image) {
            $sucess[] = $mime->addHTMLImage($image,"image/png");
        }


        $smtp = Mail::factory('smtp',
            array ('host' => $this->host,
                'auth' => true,
                'username' => $this->username,
                'password' => $this->password));

        $body = $mime->get();
        $headers = $mime->headers($headers);

        $mail = $smtp->send($this->to, $headers, $body);

        if (PEAR::isError($mail)) {
            //echo("<p>" . $mail->getMessage() . "</p>");
            return false;
        }

        return true;
    }
}