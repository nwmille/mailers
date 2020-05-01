<?php

namespace App;


use Exception;
use Carbon\Carbon;
use PhpMimeMailParser\Parser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;


class Email extends Parser
{
    private $serverConnection;
    private $attachments = null;
    private $inlineAttachments = array();
    private $savedFiles = array();
    private $UID;
    public $pdfFileNames = array();
    public $validAttachments = array();
    public $invalidAttachments = array();
    public $from = array();
    public $subject;

    public function __construct(Mailbox $mailbox, $email_UID)
    {
        parent::__construct();

        $this->serverConnection = $mailbox->getServerConnection();

        try
        {
            $emailStructure = imap_fetchbody($this->serverConnection, $email_UID, "", FT_UID);
            $this->setText($emailStructure);
            $this->setFrom();
            $this->setSubject();
            $this->UID = $email_UID;
            $this->attachments = $this->getAttachments(false);

        } catch (Exception $e)
        {
            error_log($e);
            if(!imap_ping($this->serverConnection))
            {
                error_log("+++++CONNECTION DROPPED+++++");
            }
        }
    }


    public function __destruct()
    {
        parent::__destruct();
    }


    private function setFrom()
    {

        foreach($this->getAddresses('from') as $addressSet)
        {
            $isValid = filter_var($addressSet['address'], FILTER_VALIDATE_EMAIL);

            if ($isValid !== false)
            {
                $this->from[] = $addressSet['address'];
            }
        }

        if (empty($this->from))
        {
            $this->from[] = "noreply@noreply.com";
        }
    }


    private function setSubject()
    {
        $this->subject = $this->getHeader('subject');
    }


    public function getUID()
    {
        return $this->UID;
    }


    public function getServerConnection()
    {
        return $this->serverConnection;
    }


    public static function getDomain($emailAddress)
    {
        if (is_array($emailAddress) && count($emailAddress) === 1)
        {
            list($emailAddress) = $emailAddress;
        }
        list(,$domain) = explode('@', $emailAddress);
        $domain = strstr($domain, '.', true);
        return $domain;
    }


    public static function getUsername($emailAddress)
    {
        list($username, ) = explode('@',$emailAddress);

        return $username;
    }


    public function getAttachmentFileNames()
    {
        $retVal = array();
        foreach ($this->attachments as $attachment)
        {
            $retVal[] = $attachment->getFilename();
        }

        return $retVal;
    }


    public function hasPdfAttachment($storePDFdir = null)
    {
        $retVal = false;

        if(count($this->attachments) > 0)
        {
            foreach ($this->attachments as $attachment)
            {
                $fileType = $attachment->getContentType();
                $origFileName = strtolower($attachment->getFilename());

                if ($fileType == "application/pdf" || ($fileType == "application/octet-stream" && strpos (strtolower ($origFileName), '.pdf')))
                {
                    $retVal = true;

                    if($storePDFdir != null)
                    {

                        // make unique file name with unix timestamp
                        $fileName = Carbon::now()->timestamp;
                        $pdfData = $attachment->getContent();
                        $path = $storePDFdir . $fileName;

                        while(is_file($path.'.pdf'))
                        {
                            $fileName = Carbon::now()->timestamp;
                            $path = $storePDFdir . $fileName;
                        }

                        try
                        {
                            file_put_contents($path.".pdf", $pdfData);
                            chmod($path.".pdf", 0774);
                            $this->pdfFileNames[$origFileName] = $fileName;
                        }
                        catch (exception $e)
                        {
                            continue;
                        }

                    }

                }
            }
        }

        return $retVal;
    }

    public function hasAttachment($approvedFileTypes = array(), $storeFileDIR = null)
    {
        $fileTypes = array(
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'rtf' => 'application/rtf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'null' => 'application/octet-stream'
        );

        $retVal = false;

        $attachments = $this->getAttachments(false);

        if(count($attachments) > 0)
        {
            $fileName = "";
            foreach ($attachments as $attachment)
            {
                $fileType = $attachment->getContentType();
                $origFileName = $attachment->getFilename();

                $fileTypeMatched = array_search($fileType, $fileTypes);

                $fileExtension = '.'.$fileTypeMatched != false ? $fileTypeMatched : null;

                if (in_array($fileTypeMatched, $approvedFileTypes) || strpos(strtolower($origFileName), '.pdf') !== false)
                {
                    $retVal = true;

                    if($storeFileDIR != null)
                    {
                        // make unique file name with unix timestamp
                        $rightNow = Carbon::now()->timestamp;
                        $pdfData = $attachment->getContent();
                        $path = "";

                        if($rightNow == $fileName || $fileName == "")
                        {
                            do
                            {
                                $fileName = Carbon::now()->timestamp;
                                $path = $storeFileDIR . $fileName;
                            } while ($rightNow == $fileName);
                        }

                        $this->validAttachments[$origFileName] = array('file_name'=>$fileName, 'save_path'=>$storeFileDIR, 'file_ext'=>strtolower($fileExtension), 'full_path'=>$storeFileDIR.$fileName.".".$fileExtension);

                        try
                        {
                            file_put_contents($path.'.'.$fileExtension, $pdfData);
                            $this->savedFiles[$fileTypeMatched][] = $fileName;
                        }
                        catch (exception $e)
                        {
                            continue;
                        }

                    }

                }
                else
                {
                    $this->invalidAttachments[] = $origFileName;
                }
            }
        }
        return $retVal;
    }



    public function forwardEmail(array $emailArg = ['to' => null, 'from' => null, 'subject' => null, 'cc' => null ] )
    {
        $retVal = false;

        // getMessageBody() with mime type 'html'
        $body = $this->getMessageBody('html');
        $type = 'html';

        // check if the body is empty. getMessageBody() with mime type 'text' if it is empty.
        if (preg_match('/\S/', $body) == 0)
        {
            $body = $this->getMessageBody('text');
            $type = 'text';
        }

        if (is_array($this->from))
        {
            $this->from = implode(';', $this->from);
        }

        foreach ($emailArg as $key=>$value)
        {
            switch ($key)
            {
                case "to":
                    $emailElements["to"] = is_null($emailArg["to"]) ? env("AP_MAIL_FORWARD_TO") : $emailArg["to"];
                    break;
                case "from":
                    $emailElements["from"] = is_null($emailArg["from"]) ? $this->from : $emailArg["from"];
                    break;
                case "subject":
                    $emailElements["subject"] = is_null($emailArg["subject"]) ? $this->subject  : $emailArg["subject"];
                    break;
                case "cc":
                    $emailElements["cc"] = is_null($emailArg["cc"]) ? ""  : $emailArg["cc"];
                    break;
            }
        }

        $emailElements["body"] = $body;



//        $emailElements = array(
//            "to" => env("AP_MAIL_FORWARD_TO"),
//            "from" => $this->from,
//            "subject" => $this->subject,
//            "body" => $body
//        );

        // get ALL the atteachments (inline attachments included)
        $this->attachments = $this->getAttachments();

        // filter so that we only get inline attachments
        foreach ($this->attachments as $attachment)
        {
            if ($attachment->getContentDisposition() == "inline")
            {
                $this->inlineAttachments[] = $attachment;
            }
        }


        if (preg_match('/\S/', $body) == 0)
        {

            try
            {
                Mail::send([], [], function ($message) use ($emailElements)
                {
                    if ($emailElements["cc"])
                    {
                        $message->cc($emailElements["cc"]);
                    }
                    $message->subject($emailElements["subject"]);
                    if ($this->attachments !== NULL)
                    {
                        //                foreach ($this->attachments as $fileName => $attachment)
                        foreach ($this->attachments as $attachment)
                        {
                            $message->attachData($attachment->getContent(), $attachment->getFileName(), [
                                'mime' => $attachment->getContentType(),
                            ]);
                        }
                    }

                    if (count($this->inlineAttachments) > 0)
                    {
                        foreach ($this->inlineAttachments as $inlineAttachment)
                        {
                            $message->embedData($inlineAttachment->getContent(), $inlineAttachment->getContentID(),
                                $inlineAttachment->getContentType());
                        }

                    }

                    $message->to($emailElements["to"])
                        ->from(filter_var($emailElements["from"], FILTER_SANITIZE_EMAIL));

                });

                $retVal = true;

            } catch (Exception $e)
            {
                $message = "There has been an error with forwarding an email.".PHP_EOL.
                    "Action required. Please consult the documentation, reference error code:
                    REF#0002".PHP_EOL.
                    "Email failed to forward.".PHP_EOL.
                    "SUBJECT: ".$emailElements['subject'].PHP_EOL.
                    "FROM: ".$emailElements['from'].PHP_EOL.
                    "TIME OF ERROR: ".Carbon::now('America/Chicago').PHP_EOL;
                ;

                Log::channel("daily")->error("++++++++++FOWARD EMAIL FAILURE++++++++++".PHP_EOL, [$message, $emailElements]);
                Log::channel("daily")->error($e);

                mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure to forward email', $message . PHP_EOL . $e);
            }

        } else
        {

            try
            {
                Mail::send([$type => 'email'], ['emailbody' => $body], function ($message) use ($emailElements)
                {
                    if ($emailElements["cc"])
                    {
                        $message->cc($emailElements["cc"]);
                    }

                    $message->subject($emailElements["subject"]);
                    if ($this->attachments !== NULL)
                    {
                        //                foreach ($this->attachments as $fileName => $attachment)
                        foreach ($this->attachments as $attachment)
                        {
                            $message->attachData($attachment->getContent(), $attachment->getFileName(), [
                                'mime' => $attachment->getContentType(),
                            ]);
                        }
                    }

                    if (count($this->inlineAttachments) > 0)
                    {
                        foreach ($this->inlineAttachments as $inlineAttachment)
                        {
                            $message->embedData($inlineAttachment->getContent(), $inlineAttachment->getContentID(),
                                $inlineAttachment->getContentType());
                        }

                    }
                    $message->to($emailElements["to"])
                        ->from(filter_var($emailElements["from"], FILTER_SANITIZE_EMAIL));

                });

                $retVal = true;

            } catch (Exception $e)
            {
                $message = "There has been an error with forwarding an email.".PHP_EOL.
                    "Action required. Please consult the documentation, reference error code: REF#0002".PHP_EOL.
                    "Email failed to forward.".PHP_EOL.
                    "SUBJECT: ".$emailElements['subject'].PHP_EOL.
                    "FROM: ".$emailElements['from'].PHP_EOL.
                    "TIME OF ERROR: ".Carbon::now('America/Chicago').PHP_EOL .
                    "http://wiki.ipipes.com/index.php/Mailer#REF.230002";

                Log::channel("daily")->error("==========FOWARD EMAIL FAILURE==========", $emailElements);
                Log::channel("daily")->error($e);

                mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure to forward email', $message . PHP_EOL . $e);
            }


        }

        return $retVal;
    }


    public function moveMail(string $mailboxName, array $emails)
    {
        $retVal = false;
        foreach ($emails as $email)
        {
            imap_mail_copy($this->serverConnection, $email, $mailboxName,CP_UID | CP_MOVE);
            $retVal = imap_setflag_full($this->serverConnection, $email, "\\Flagged \\Deleted \\SEEN",CP_UID);
        }

        imap_expunge($this->serverConnection);

        return $retVal;
    }


    public function getSavedFiles()
    {
        return $this->savedFiles;
    }
}
