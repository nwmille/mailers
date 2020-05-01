<?php

namespace App\Http\Controllers\QuoteOmatic;

use App\Email;
use App\Mailbox;
use App\Pdf2xl;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;



class QuoteMailer extends Controller
{

    protected $knownLayout;
    protected $attachmentFileNames;
    public $layout;


    public function __construct()
    {

        // init log stuff
        ini_set("log_errors", 1);
        ini_set("error_log", "/opt/ips/log/QuoteMailerError.log");
        error_log(Carbon::now()->toDateTimeString());

        $quotes_layouts_dir = Storage::disk('PDF2XL')->path(env("QUOTEMAILER_PDF2XL_LAYOUTS"));

        // create a new connection to the mail server
        $quotes = new Mailbox(env("QUOTE_USERNAME"), env("QUOTE_PASSWORD"));


        // search inbox
        $quotes->search();

        if (!$quotes->emails)
        {
            imap_errors();
            imap_alerts();
            imap_close($quotes->getServerConnection(), CL_EXPUNGE);
            exit(0);
        }


        // Get known domain names to check against.
        // This gets an array of dir names from the server where we
        // keep the mapped PDF layouts. The layouts are named as
        // as follows; {domainName}.{fileExtension}.
        $mappedLayouts = scandir($quotes_layouts_dir);

        // remove the '.{fileExtension}' so that we get just the domain name
        foreach ($mappedLayouts as $fileName)
        {
            $length = strlen(substr($fileName, strpos($fileName, '.')));
            $domain = substr($fileName, 0, -$length);

            if ($domain != false)
            {
                $this->knownLayout[] = strtoupper($domain);
            }
        }

        // If there are emails, process them
        if ($quotes->emails)
        {
            $this->processEmails($quotes);
        }
    }



    private function processEmails(Mailbox $apMail)
    {
        $move = array();
        $numEmails = count($apMail->emails);

        foreach ($apMail->emails as $key => $accountingEmail)
        {
            $email = new Email($apMail, $accountingEmail);
            $layoutKey = trim($email->getHeader('subject'));
            $tmp = Storage::disk('PDF2XL')->path(env("QUOTEMAILER_PROCESSING_PDF"));

            $rsa = env('QUOTEMAILER_PDF2XL_RSA');
            $pdf_in = env('QUOTEMAILER_PDF2XL_IN');
            $pdf_out = env('QUOTEMAILER_PDF2XL_OUT');
            $layout_dir = env('QUOTEMAILER_PDF2XL_LAYOUT_DIR');

            $process = array();
            $process['has_layout'] = false;
            $process['has_layout'] = $this->knownLayout($layoutKey);

            if ($process['has_layout'] === false)
            {
                $this->layout = $layoutKey;
            }


            $process['good_attachment'] = $email->hasAttachment(array('pdf', 'txt'), $tmp);


            if ($process['good_attachment'])
            {

                $processedFileNames = array();
                $hasPDF = false;

                foreach ($email->getSavedFiles() as $fileType => $arrayOfFiles)
                {
                    switch ($fileType)
                    {
                        case 'txt':
                            foreach ($arrayOfFiles as $fileName)
                            {
                                $from = $email->getRawHeader('from');
                                $text = $this->layout . '|' . $from . "\r\n";
                                $this->prepend($text, $tmp . $fileName . '.txt');
                                $process['ftp_file'] = Storage::disk('ftp_unidata')->put($fileName . '.txt', Storage::disk('public_quoteMailer')->get($fileName . '.txt'), 'public');
                                Storage::disk('public_quoteMailer')->delete($fileName . '.txt');
                            }
                            break;
                        case 'pdf':
                            $hasPDF = true;

                            if (!$process['has_layout'])
                            {
                                break;
                            }

                            // process PDF2XL
                            $processPDF = new Pdf2xl($pdf_in, $pdf_out, $layout_dir, $rsa);
                            $processedFileNames = $processPDF->run($arrayOfFiles, $this->layout);
                            $process['file_processed'] = true;
                            break;
                    }

                }

                if(!$hasPDF)
                {
                    $process['has_layout'] = true;
                }

                foreach ($processedFileNames as $fileName => $wasCompleted)
                {
                    if (!$wasCompleted || !file_exists($tmp.$fileName.".csv"))
                    {
                        Storage::disk('public')->delete($fileName . '.pdf');
                        if (Storage::disk('public')->exists($fileName.'.csv'))
                        {
                            Storage::disk('public')->delete($fileName.'.csv');
                        }
                        $process['file_processed'] = false;
                    }
                    else
                    {
                        $from = $email->getRawHeader('from');
                        $text = $this->layout.'|'.$from."\r\n";

                        $this->prepend($text, $tmp.$fileName.'.csv');
                        $process['ftp_file'] = Storage::disk('ftp_unidata')->put($fileName . '.csv', Storage::disk('PDF2XL')->get(env("QUOTEMAILER_PROCESSING_PDF").$fileName.'.csv'), 'public');

                        Storage::disk('PDF2XL')->delete(env("QUOTEMAILER_PROCESSING_PDF").$fileName.'.csv');
                        Storage::disk('PDF2XL')->delete(env("QUOTEMAILER_PROCESSING_PDF").$fileName.'.pdf');
                    }
                }
            }


            $errorFound = false;
            $errors = array();

            foreach ($process as $processName => $completed)
            {

//                $completed = false;
                switch ($processName)
                {
                    case 'has_layout':
                        if(!$completed)
                        {
                            $errors[] = "No layout found";
                            $errorFound = true;
                        }
                        break;
                    case 'has_pdf':
                        if(!$completed)
                        {
                            $errors[] = "Could not find PDF attachment";
                            $errorFound = true;
                        }
                        break;
                    case 'file_processed':
                        if(!$completed)
                        {
                            $errors[] = "Error processing pdf through converter";
                            $errorFound = true;
                        }
                        break;
                    case 'ftp_file':
                        if(!$completed)
                        {
                            $errors[] = "FTP send error to Prelude";
                            $errorFound = true;
                        }
                        break;
                    default:
                        break;
                }
            }


            $importReportBody = '';

            if ($errorFound)
            {
                $importReportSubject = "RE: ".$email->getHeader('subject');
                $importReportBody .= "Error processing quote"."\r\n";
                $importReportBody .= "The following error(s) were encountered:\r\n";

                foreach ($errors as $error)
                {
                    $importReportBody .= "$error\r\n";
                }

                $to = $email->getAddresses('from');
                list(, $to,) = array_values($to[0]);

                $this->sendEmailResponse($to, 'quote.import@ipipes.com', $importReportSubject, $importReportBody);

            }
            else
            {
//                $importReportSubject = "Quote import success";
//                $importReportBody = "Your recent quote for ".$email->getHeader('subject')." has completed successfully.";
            }


            $move[] = $accountingEmail;
            --$numEmails;

            // move emails after processing them.
            if ($numEmails == 0)
            {
                $email->moveMail('processed', $move);
            }

        }

        imap_errors();
        imap_alerts();
        imap_close($apMail->getServerConnection(), CL_EXPUNGE);

        return;

    }

    private function prepend($string, $orig_filename)
    {
        $context = stream_context_create();
        $orig_file = fopen($orig_filename, 'r', 1, $context);

        $temp_filename = tempnam(sys_get_temp_dir(), 'php_prepend_');
        file_put_contents($temp_filename, $string);
        file_put_contents($temp_filename, $orig_file, FILE_APPEND);

        fclose($orig_file);
        unlink($orig_filename);
        rename($temp_filename, $orig_filename);
    }


    /**
     * Check if the given email address' domain is in an array of 'known domain'
     *
     * @param $key
     * @return bool
     */
    private function knownLayout($key)
    {
        $retVal = false;

        foreach ($this->knownLayout as $knownDomain)
        {
            if (strpos($key, $knownDomain) !== false)
            {
                $this->layout = $knownDomain;
                $retVal = true;
            }
        }

        return $retVal;
    }


    /** Read the output of the PDF2XL program and verify that
     *  the fields provided have the correct type of values
     *
     * @param array $outputFile
     * @param array $fieldsToCheck
     * @return boolean
     */
    public function validateOutput(array $outputFiles, array $fieldsToCheck)
    {

        $retVal = false;

        foreach ($outputFiles as $outputFile)
        {
            $fileExist = is_file(env('APMAILER_PDF2XL_PDF') . $outputFile . '.csv');

            if ($fileExist != true)
            {
                $retVal =  false;
            } else
            {
                $handle = fopen(env('APMAILER_PDF2XL_PDF') . $outputFile.'.csv', 'r');

                while ($row = fgets($handle))
                {
                    foreach ($fieldsToCheck as $fieldName => $fieldType)
                    {
                        if (str_contains($row, $fieldName))
                        {
                            $strArray = str_getcsv($row, '|');
                            $key = array_search($fieldName, $strArray);

                            var_dump($strArray);

                            if ($strArray[$key + 1] !== '')
                            {
                                $retVal = true;
                            } else
                            {
                                $retVal = false;
                            }
                        }
                    }
                }
            }
        }

        return $retVal;
    }

    private function sendEmailResponse($to, $from, $subject, $body)
    {

        $emailElements = array(
            "to" => $to,
            "from" => $from,
            "subject" => $subject,
            "emailbody" => $body
        );

        Mail::send(['text' => 'email'], ['emailbody' => $body], function ($message) use ($emailElements)
        {
            $message->subject($emailElements["subject"])
                ->to($emailElements["to"])
                ->from($emailElements["from"]);
        });

    }




}
