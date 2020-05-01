<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Email;
use App\Mailbox;
use App\Pdf2xl;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ApIndexer extends Controller
{
    protected $log;
    private $acceptedFileTypes = array (
        "pdf",
        "txt",
        "rtf",
        "doc",
        "docx"
    );
    private $ap_email_addresses = array (
        "ap1",
        "ap2",
        "ap3",
        "ap4",
        "ap5",
    );
    protected $receivedFrom;
    private $email;

    public function __construct()
    {
        // init log stuff
        $this->log = Log::channel('ap_indexer');

        // create a new connection to the mail server
        $apIndexerMail = new Mailbox(env("AP_INDEXER_USERNAME"), env("AP_INDEXER_PASSWORD"));

        // search inbox
        $apIndexerMail->search();

        // If there are emails, process them
        if ($apIndexerMail->emails)
        {
            $this->processEmails($apIndexerMail);
        }

    }

    private function processEmails(Mailbox $apIndexerMail)
    {
        $mailBag = 0;
        $move = array();
        $numEmails = count($apIndexerMail->emails);

        $this->logIt("starting", ["num_emails"=>count($apIndexerMail->emails)]);

        foreach ($apIndexerMail->emails as $key => $accountingIndexEmail)
        {
//            $email = new Email($apIndexerMail, $accountingIndexEmail);
            $this->email = new Email($apIndexerMail, $accountingIndexEmail);
            $manualIndexStore = Storage::disk('public')->path(env('AP_INDEX_PDF'));
            $from = $this->receivedFrom = $this->email->from[0];
            $fromUsername = "";

            $this->logIt("email_info", ["email_subject"=>$this->email->subject, "email_from"=>$from]);

            $hasValidAttachment = $this->email->hasAttachment($this->acceptedFileTypes, $manualIndexStore);

            if ($this->email->invalidAttachments)
            {
                // nothing to delete just report and move on (email sent)
                $this->logIt("attachments_invalid", ["invalid_attachments"=>$this->email->invalidAttachments, "from"=>$from]);
            }


            if ($hasValidAttachment)
            {
                $this->logIt("valid_attachments", ["files"=>$this->email->validAttachments]);

                $failedConversions = $this->allToPDF($this->email->validAttachments);

                if ($failedConversions)
                {
                    $this->logIt("attachment_conversion_failed", ["failed_converssion"=>$failedConversions, "from "=>$from]);
                }

                $this->sendToIndexer($this->email->validAttachments);

            }
            else
            {
                $now = Carbon::now()->timestamp;
                $tempStore = Storage::disk('public')->path("temp/");
                $htmlFile = $fromUsername.$now.".html";
                $pdfFile =  $fromUsername.$now.".pdf";
                $bod = $this->email->getMessageBody('html');

                file_put_contents($tempStore.$htmlFile, $bod);
                exec("xvfb-run wkhtmltopdf --no-background " .$tempStore.$htmlFile." ".$tempStore.$pdfFile);

                $foo = copy("/opt/ips/APmailer/storage/app/public/temp/".$pdfFile, "/opt/ips/APmailer/storage/app/public/APindex/pdf/".$pdfFile);
                $faz = unlink("/opt/ips/APmailer/storage/app/public/temp/".$pdfFile);
            }


            foreach ($this->email->validAttachments as $origFname => $file)
            {
                $file = $file['save_path'].$file['file_name'].".".$file['file_ext'];
                if(file_exists($file))
                {
                    unlink($file);
                }
            }

            $move[] = $accountingIndexEmail;
            $mailBag++;
            --$numEmails;

            // Previously had issues with it failing to move a large batch
            // of processed emails to the 'PROCESSED' folder. If the program
            // processes 500 emails it will move that batch and continue, to
            // prevent from choking on a large batch of emails.
            if ($mailBag == 500 || $numEmails == 0)
            {
                $this->email->moveMail('Processed', $move);
                $mailBag = 0;
            }

        }

        $this->logIt("ending");

        imap_errors();
        imap_alerts();
        imap_close($apIndexerMail->getServerConnection(), CL_EXPUNGE);

        return;

    }


    private function allToPDF(&$attachments)
    {
        $tempStore = Storage::disk('public')->path("temp");
        $failedConversions = false;

        $this->logIt("convert_start");

        foreach ($attachments as $origFname => $fileInfo)
        {

            if ($fileInfo['file_ext'] != "pdf" && file_exists($fileInfo['full_path']))
            {
                $command = "libreoffice --headless --convert-to pdf:writer_pdf_Export --outdir ".$tempStore." ".$fileInfo['full_path'];
                $this->logIt("convert_processing", ["file_name"=>$fileInfo['file_name'], "command"=>$command]);

                exec("libreoffice --headless --convert-to pdf:writer_pdf_Export --outdir ".$tempStore." ".$fileInfo['full_path'] , $stdout);

                $this->logIt("convert_stdout", ["stdout"=>$stdout]);

                if (!file_exists($fileInfo['save_path'].$fileInfo['file_name'].".pdf"))
                {
                    $deleteFile = "no file to delete";
                    $failedConversions[] = $origFname;
                    if(file_exists($fileInfo['save_path'].$fileInfo['file_name'].".pdf"))
                    {
                        $deleteFile = unlink($fileInfo['save_path'].$fileInfo['file_name'].".pdf");
                    }
                    unset($attachments[$origFname]);
                    $this->logIt("convert_out_not_found", ["deleted_file"=>$deleteFile]);
                }
                else
                {
                    $this->logIt("convert_found");
                }
            }
        }

        return $failedConversions;

    }

    private function sendToIndexer($arrayOfFiles)
    {
        $indexerPath = Storage::disk('APindex_pdfs')->path("");
        $tmpPath = Storage::disk('public')->path(env("AP_INDEX_PDF"));
//        $indexerPath = Storage::disk('public')->path(env("AP_INDEX_PDF")."faz/");

        $this->logIt("indexer_sending");

        foreach ($arrayOfFiles as $key=>$value)
        {
            $transferFrom = $value['save_path'].$value['file_name'].".pdf";

            if (str_contains(strtolower($this->email->subject), "ap"))
            {
                foreach ($this->ap_email_addresses as $ap_address)
                {
                    if (str_contains(strtolower($this->email->subject), $ap_address))
                    {
                        $sendFileTo = $indexerPath.$ap_address."!".$value['file_name'].".pdf";
                    }
                }
            }
            elseif (str_contains($this->receivedFrom, "ipipes"))
            {
                $fromUsername = Email::getUsername($this->receivedFrom)."!";
                $sendFileTo = $indexerPath.$fromUsername.$value['file_name'].".pdf";
            }
            else
            {
                $sendFileTo = $indexerPath.$value['file_name'].".pdf";
            }

            $renamedComplete = "NO";
            try
            {
                $renamedComplete = copy($transferFrom, $sendFileTo);
                dump($renamedComplete);
                unlink($transferFrom);
            }
            catch (\Exception $e)
            {
                $this->logIt("indexer_failed_to_move",["error"=>$e, "original_file_name"=>$key, "from"=>$this->receivedFrom]);
                $this->logIt("catch", ["e"=>$e]);
            }

            $this->logIt("indexer_send_to", ["transfer_from"=>$transferFrom, "send_to"=>$sendFileTo, "renamed_complete"=>$renamedComplete]);
        }
    }


    private function logIt($whichLog, array $logData = null)
    {

        switch ($whichLog)
        {
            case "starting":
                $this->log->info("========== STARTING ==========");
                $this->log->info("Emails found:  ".$logData["num_emails"]);
                break;
            case "email_info":
                $this->log->info("FROM: ".$logData["email_from"]);
                $this->log->info("SUBJECT:  ".$logData["email_subject"]);
                break;
            case "valid_attachments":
                $this->log->info("Valid attachments found:");
                foreach ($logData["files"] as $file)
                {
                    $this->log->info($file);
                }
                break;
            case "attachments_invalid":
                $files = "";
                $this->log->error("INVALID FILE TYPE(s) FOUND AS ATTACHMENT");

                $message = "There was an error processing your attachment(s). Below is a list of files that were NOT uploaded." . PHP_EOL;
                foreach ($logData["invalid_attachments"] as $origfname)
                {
                    $message .= $origfname . PHP_EOL;
                    $files .= $origfname." ";
                }
                $message .= PHP_EOL."Accepted file types:".PHP_EOL;
                foreach ($this->acceptedFileTypes as $fileType)
                {
                    $message .= $fileType . PHP_EOL;
                }
                $message .= PHP_EOL."If you have questions contact help@ipipes.com".PHP_EOL;

                $this->log->error("INVALID FILES; ".$files);
                $this->log->error("Ignoring invalid files and continuing.".$files);

                mail($logData["from"], 'APindexer ***ERROR*** Failure to convert document', $message . PHP_EOL);
                break;
            case "attachment_conversion_failed":
                $message = "There was an error converting your attachment(s). Below is a list of files that failed during conversion:".PHP_EOL;
                foreach ($logData["failed_converssion"] as $origfname)
                {
                    $message .= $origfname . PHP_EOL;
                }
                $message .= PHP_EOL."Accepted file types:".PHP_EOL;
                foreach ($this->acceptedFileTypes as $fileType)
                {
                    $message .= $fileType . PHP_EOL;
                }
                $message .= PHP_EOL."If you have questions contact help@ipipes.com".PHP_EOL;
                mail($logData["from"], 'APindexer ***ERROR*** Failure to convert document', $message . PHP_EOL);
                break;
            case "convert_start":
                $this->log->info("Checking if documents need to be converted to pdf");
                break;
            case "convert_processing":
                $this->log->info("File: ".$logData["file_name"]);
                $this->log->info("Command: ".$logData["command"]);
                break;
            case "convert_stdout":
                $this->log->info("Output: ");
                foreach ($logData["stdout"] as $line)
                {
                    $this->log->info($line);
                }
                break;
            case "convert_out_not_found":
                $this->log->error("Did not find the converted file after running the conversion.");
                $this->log->error("Delete failed .pdf file: ".$logData["deleted_file"]);
                break;
            case "convert_found":
                $this->log->info("File successfully converted to .pdf");
                break;
            case "indexer_send_to":
                $this->log->info("Sending files to manual indexer dir.");
                $this->log->info("Sending from: ".$logData["transfer_from"]);
                $this->log->info("Sending to: ".$logData["send_to"]);
                $this->log->info("File move completed: ".$logData["renamed_complete"]);
                break;
            case "indexer_sending":
                $this->log->info("Sending files to indexer dir.");
                break;
            case "indexer_failed_to_move":
                $message = "An error has occurred while moving .pdf file to the manual indexer.".PHP_EOL;
                $message .= "File name: ".$logData["original_file_name"];
                $message .= "Exception: ".$logData["error"];

                $this->log->error($message);
                mail($logData["from"], 'APindexer ***ERROR*** Failure to move document', $message . PHP_EOL);
                break;
            case "catch":
                $this->log->error("CAUGHT ERROR:");
                $this->log->error($logData["e"]);
                break;
            case "ending":
                $this->log->info("========== ENDING ==========");
                break;


        }

    }



}
