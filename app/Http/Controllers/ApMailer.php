<?php

namespace App\Http\Controllers;

use App\Email;
use App\Mailbox;
use App\Pdf2xl;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Integer;


class ApMailer extends Controller
{
    protected $knownDomains;
    protected $filesToProcess;
    protected $filesToSave;
    public $layout;
    protected $testing;
    protected $log;
    protected $apRules;

    public function __construct()
    {
        $this->log = Log::channel('daily_ap_mailer');

        $ap_layouts_dir = Storage::disk('PDF2XL')->path(env("APMAILER_PDF2XL_LAYOUTS"));

        // create a new connection to the mail server
        $apMail = new Mailbox(env("AP_USERNAME"), env("AP_PASSWORD"), "TEST");

        // search inbox
        $apMail->search();

        if (!$apMail->emails)
        {
            imap_errors();
            imap_alerts();
            $apMail->closeConnection();
            $this->log->info('FOUND 0 EMAILS. ');
            $this->log->info('APPLICATION NORMAL EXIT');
            exit(0);
        }

        // Get known domain names to check against.
        // This gets an array of dir names from the server where we
        // keep the mapped PDF layouts. The layouts are named as
        // as follows; {domainName}.{fileExtension}.
        $attempts = 0;

        do
        {
            try
            {
                $mappedDomains = scandir($ap_layouts_dir);
            }
            catch (\ErrorException $e)
            {
                if ($attempts >= 3)
                {
                    $message = 'APmailer has failed ' . $attempts . ' times to open ' . $ap_layouts_dir . PHP_EOL .
                        'Action required. Please consult the documentation, reference error code: REF#0001' . PHP_EOL .
                        "http://wiki.ipipes.com/index.php/Mailer#REF.230001";

                    $this->log->critical($message);

                    mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure to read layout directory', $message . PHP_EOL . $e);
                    exit();
                }
                else
                {
                    $this->log->error('Failed to open APmailer layout dir: ' . $ap_layouts_dir);
                    $this->log->error('Attempt: ' . $attempts);
                    $this->log->error('Sleeping for 2 seconds and trying again....');
                    sleep(2);
                }

                $attempts++;
                continue;
            }
            break;
        }
        while ($attempts <= 3);

        // remove the '.{fileExtension}' so that we get just the domain name
        foreach ($mappedDomains as $fileName)
        {
            $length = strlen(substr($fileName, strpos($fileName, '.')));
            $domain = substr($fileName, 0, -$length);

            if ($domain != false)
            {
                $this->knownDomains[] = strtoupper($domain);
            }
        }

        // If there are emails, process them
        if ($apMail->emails)
        {
            $this->log->info('FOUND ' . count($apMail->emails) . ' EMAILS. PROCESSING....');
            $this->log->info("****************START***************");
            $this->processEmails($apMail);
        }

        imap_errors();
        imap_alerts();
        $apMail->closeConnection();
    }

    private function processEmails(Mailbox $apMail)
    {
        $processed = 0;
        $forwarded = 0;
        $failed = 0;

        $numEmails = count($apMail->emails);
//        $tmp = Storage::disk('PDF2XL')->path(env("APMAILER_PROCESSING_PDF"));
        $tmp = Storage::disk('PDF2XL')->path(env("APMAILER_PROCESSING_PDF"));
//        $processedPath = Storage::disk('PDF2XL')->path(env("APMAILER_PROCESSED_PDF"));
        $rsa = env('APMAILER_PDF2XL_RSA');
        $pdf_in = env('APMAILER_PDF2XL_IN');
        $pdf_out = env('APMAILER_PDF2XL_OUT');
        $layout_dir = env('APMAILER_PDF2XL_LAYOUTS_DIR');
        $this->cacheAPrules();


        foreach ($apMail->emails as $key => $emailUID)
        {

            //create email obj
            $email = new Email($apMail, $emailUID);
            $this->layout = null;
            $this->filesToSave = array();
            $this->filesToProcess = array();

            if (count($email->from) === 1)
            {
                list($email->from) = $email->from;
            }
            else
            {
                $this->forward($email);
                $this->logIt("too_many_cooks", ["subject"=>$email->subject]);
                $forwarded++;
                continue;
            }

            $this->logIt("email_start", ["key"=>$key, "numEmails"=>$numEmails, "emailSubject"=>$email->subject, "emailFrom"=>$email->from]);

//            catch repeating emails
            if($this->repeating($emailUID, $email->subject))
            {
                $sent = $this->forward($email);
                $this->logIt("mailer_stuck", ["sent" => $sent]);
                $forwarded++;
                continue;
            }


            if(!$email->hasPdfAttachment($tmp))
            {
                $this->forward($email);
                $this->logIt("pdf_not_found", ["subject"=>$email->subject]);
                $forwarded++;
                continue;
            }

            //set layout
            if (strtoupper(Email::getDomain($email->from)) === "IPIPES")
            {
                $this->layout = $this->validLayout($email->subject);
                $knownDomain = $this->layout;
                if (!$this->layout)
                {
                    $this->forward($email);
                    $this->logIt("too_many_cooks");
                    $forwarded++;
                    continue;
                }
            }
            else
            {
                $knownDomain = $this->knownDomain($email->from, $email);
            }

            //should we process
            if ($knownDomain)
            {
                $this->logIt("pdf_saved", ["pdfFileNames"=> $this->filesToSave, "tmp"=>$tmp]);

                // process PDF2XL
                $process = new Pdf2xl($pdf_in, $pdf_out, $layout_dir, $rsa);
                $processedFiles = $process->run($this->filesToProcess, $this->layout);

                $failedCounter= 0;

                //move files around
                foreach ($processedFiles as $fileName => $booleanProcessed)
                {
                    if (file_exists($tmp . $fileName . '.csv'))
                    {
                        if ($this->testing||true)
                        {
                            //prepend data for rick/prelude and send csv to prelude
                            $prepend = array("LAYOUT|".strtolower($this->layout).PHP_EOL, "EMAIL|".strtolower($email->from).PHP_EOL);
                            $this->prepend($prepend, Storage::disk('processing_pdfs')->path($fileName . '.csv'));
                            $sent = Storage::disk('apmailer_unidata_TEST')->put($fileName . '.csv', Storage::disk('processing_pdfs')->get($fileName . '.csv'), 'public');
                            $this->logIt("csv_sent", ["sent"=>$sent]);
                        }

                        try
                        {
                            //delete local copy
                            $deleteCSV = unlink($tmp . $fileName . '.csv');
                        }
                        catch (\ErrorException $e)
                        {
                            $deleteCSV = false;
                        }
                        $this->log->info("Deleted csv: " . json_encode($deleteCSV));
                    }
                    else
                    {
                        foreach ($this->filesToProcess as $originalFn => $fileToProcess)
                        {
                            if ($fileName == $fileToProcess && $booleanProcessed === false)
                            {
                                $failedTOprocess[$originalFn] = $fileToProcess;
                                $this->logIt("csv_missing", ["originalFn"=>$originalFn, "fileName"=>$fileName]);
                                $this->forward($email);
                                $forwarded++;
                                continue(3);
                            }
                        }

                    }
                }



                //delete the pdf file(s) we aren't saving
                $deleteThese = array_diff($email->pdfFileNames, $this->filesToSave);

                foreach ($deleteThese as $yek => $file)
                {
                    $foo = Storage::disk('PDF2XL')->delete($file . ".pdf");
                }

                //move the pdf file(s) we are saving into storage
                foreach ($this->filesToSave as $foo => $file)
                {
                    $processing = Storage::disk('PDF2XL')->path(env("APMAILER_PROCESSING_PDF").$file . '.pdf');
                    $processedPDF = Storage::disk('public')->path(env("APMAILER_PROCESSED_PDF").$file . ".pdf");
                    $foo = copy($processing, $processedPDF);
                    $faz = unlink($processing);
                }


                //move email to processed folder
                $moved = $email->moveMail('processed', array($email->getUID()));
                $this->log->info("Moved to processed: " . $moved);

                //Forward the mail if the layout was labeled as test
                if ($this->testing)
                {

                    $sent = $this->forward($email);
                    $this->log->info("This is *** TESTING *** forwarded email(unprocessed): " . json_encode($sent));

                    if (!$sent)
                    {
                        $message = "There has been an error with forwarding an email." . PHP_EOL .
                            "Email failed to forward." . PHP_EOL .
                            "SUBJECT: " . $email->getHeader('subject') . PHP_EOL .
                            "FROM: " . $email->getHeader('from') . PHP_EOL .
                            "TIME OF ERROR: " . Carbon::now('America/Chicago') . PHP_EOL;;

                        mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure to forward email', $message . PHP_EOL);

                        $failed++;
                    }
                }
                else
                {
                    if ($this->apRules["to"])
                    {
                        $this->apRules["to"] .= "processed";
                    }
                    $this->forward($email);
                }
                $processed++;
            }
            else
            {
                $this->forward($email);
                $forwarded++;
                continue;
            }


        }

        //meta data
        $time = Carbon::now('America/Chicago')->toTimeString();
        $this->log->info("*****************END****************");
        $this->log->info("---------------------PROCESS REPORT--------------------");
        $this->log->info("Emails read in: " . $numEmails);
        $this->log->info("Processed emails: " . $processed);
        $this->log->info("Forwarded emails: " . $forwarded);
        $this->log->info("Failed emails: " . $failed);
        $this->log->info("##stats##/$time/$numEmails/$processed/$forwarded/$failed");
        $this->log->info("-------------------------------------------------------");

        return;
    }


    /**
     * Check if the given email address' domain is in an array of 'known domain'
     *
     * @param $fromAddress
     * @param $email
     * @return string
     * @return bool
     */
    private function knownDomain(string $fromAddress, Email $email = null)
    {
        $fromDomain = strtoupper(Email::getDomain($fromAddress));

        //check for detailed rules for certain vendors
        $foundAPrules = $this->checkAPrules($fromDomain, $email);

        //no special rules found. Assign layout as normal.
        if(!$foundAPrules)
        {
            $this->layout = $this->validLayout($fromDomain);
        }


        //check for testing mode
        if (strpos(strtoupper($this->layout), 'TEST') !== false)
        {
            $this->testing = true;
            $this->log->info("**** Layout is marked as TEST ****");
        }

        if (count($this->filesToProcess) === 0)
        {
            $this->filesToProcess = $email->pdfFileNames;
        }

        if (count($this->filesToSave) === 0)
        {
            $this->filesToSave = $email->pdfFileNames;
        }

        $this->logIt("layout_using", ["layout" => $this->layout]);

        return $this->layout;
    }


    private function validLayout($layoutName)
    {
        $layoutName = strtoupper($layoutName);
        $retVal = array_intersect($this->knownDomains, array("TEST_".$layoutName, $layoutName));

        $foo = count($retVal);
        if ($foo !== 1)
        {
            $retVal = false;
        }
        else
        {
            list($retVal) = array_values($retVal);
        }

        return $retVal;
    }


    private function cacheAPrules()
    {
        $rules_last_mod = Storage::disk('unidata_ap_rules')->lastModified("APRULES.csv");
        $rules_last_cached = Cache::get('ap_rules_last_updated');

        //does cache exist or have the rules been updated?
        if(!Cache::has('ap_rules_last_updated') || ($rules_last_mod > $rules_last_cached))
        {
            $fh = fopen('ftp://username:******@xxx.xxx.xxx.xxx/ud/tmp/payables/rules/APRULES.csv', 'r');
            $n = 0;
            $vendors = array();
            while ($line = fgetcsv($fh))
            {
                if ($n == 0)
                {
                    $n++;
                    continue;
                }
                $vendors[] = $line[0];
            }
            Cache::forever('ap_rules_vendors', $vendors);
            Cache::forever('ap_rules_last_updated', $rules_last_mod);
        }
    }


    protected function checkAPrules($from, Email $email)
    {
        $vendorRules = Cache::get('ap_rules_vendors');
        $inArray = in_array($from, $vendorRules);
        $retVal = false;

        if ($inArray)
        {
            $fh = fopen('ftp://username:******@xxx.xxx.xxx.xxx/ud/tmp/payables/rules/APRULES.csv', 'r');
            $n = 0;
            $vendors = array();
            while ($line = fgetcsv($fh))
            {
                if ($n == 0)
                {
                    $n++;
                    continue;
                }

                if ($line[0] === $from)
                {

                    if ($line[1])
                    {
                        $this->apRules["to"] = $line[1];
                    }
                    else
                    {
                        $this->apRules["to"] = null;
                    }

                    foreach ($line as $key => $value)
                    {
                        //check to see if we have found a layout yet
                        if (!is_null($this->layout))
                        {
                            $retVal = true;
                            break;
                        }

                        switch ($value)
                        {
                            /*
                             * LA: If the PDF filename starts with the given reference then use the specified layout.
                             */
                            case "LA":
                                $starts_with = $line[$key+1];
                                // check attachment names here $email->pdfFileNames
                                foreach($email->pdfFileNames as $fileName => $foo)
                                {
                                    $doesMatch = strpos(strtolower($fileName), strtolower($starts_with));

                                    if ($doesMatch === 0 || $doesMatch > 0)
                                    {
                                        $this->layout = $line[$key+2];
                                    }
                                    else
                                    {
                                        continue;
                                    }
                                }
                                break;

                            /*
                             * M2: Place the PDF that matches the file name given in the 'reference' column INFRONT
                             * the other PDF. Process the PDF that DOES match through the converter.
                             */
                            case "M1":
                                //get filenames of attachments
                                $pdfFileNames = $email->pdfFileNames;

                                //rule assumes only 2 PDFs
                                if (count($pdfFileNames) != 2)
                                {
                                    break;
                                }

                                //match file name to pdf file and re-arrange array if needed
                                $pdf1 = "";
                                $pdf2 = "";
                                foreach ($pdfFileNames as $fileName => $value)
                                {
                                    if (strpos(strtolower($fileName), strtolower($line[$key+1])) === false)
                                    {
                                        $pdf1 = $value;
                                    }
                                    else
                                    {
                                        $pdf2 = $value;
                                    }
                                }

                                //new filename aka timestamp
                                $newPDF = $pdf2+5;

                                $path = Storage::disk('processing_pdfs')->path("");

                                //execute command to merge PDFs into one
                                $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=".$path.$newPDF.".pdf ".$path.$pdf1.".pdf ".$path.$pdf2.".pdf";
                                shell_exec($cmd);

                                //check if the merged file was created
                                if(!file_exists($path.$newPDF.".pdf"))
                                {
                                    $this->logIt("pdf_failed_merge", ["m2" => "m2"]);
                                    break;
                                }

                                //process the one file and save the merged file for keeping
                                $this->filesToSave = array($newPDF);
                                $this->filesToProcess = array($pdf1);
                                break;

                            /*
                             * M2: Place the PDF that matches the file name given in the 'reference' column BEHIND
                             * the other PDF. Process the PDF that does NOT match through the converter.
                             */
                            case "M2":
                                //get filenames of attachments
                                $pdfFileNames = $email->pdfFileNames;

                                //rule assumes only 2 PDFs
                                if (count($pdfFileNames) != 2)
                                {
                                    break;
                                }

                                //match file name to pdf file and re-arrange array if needed
                                $pdf1 = "";
                                $pdf2 = "";
                                foreach ($pdfFileNames as $fileName => $value)
                                {
                                    if (strpos(strtolower($fileName), strtolower($line[$key+1])) !== false)
                                    {
                                        $pdf2 = $value;
                                    }
                                    else
                                    {
                                        $pdf1 = $value;
                                    }
                                }

                                //new filename aka timestamp

                                $newPDF = $pdf2+5;

                                $path = Storage::disk('processing_pdfs')->path("");

                                //execute command to merge PDFs into one
                                $cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile=".$path.$newPDF.".pdf ".$path.$pdf1.".pdf ".$path.$pdf2.".pdf";
                                shell_exec($cmd);

                                //check if the merged file was created
                                if(!file_exists($path.$newPDF.".pdf"))
                                {
                                    $this->logIt("pdf_failed_merge", ["m2" => "m2"]);
                                    break;
                                }

                                //process the one file and save the merged file for keeping
                                $this->filesToSave = array($newPDF);
                                $this->filesToProcess = array($pdf1);
                                break;
                            case "SP":

                                // 1 pdf only
                                if (count($email->pdfFileNames) !== 1)
                                {
                                    break;
                                }
                                else
                                {
                                    $fileName = implode($email->pdfFileNames);
                                }

                                $path = Storage::disk('processing_pdfs')->path("");

                                //count pages
                                $cmd = 'gs -q -dNODISPLAY -c "('.$path.$fileName.'.pdf) (r) file runpdfbegin pdfpagecount = quit"';
                                exec($cmd, $numPages);

                                //  1 page only
                                if ((int)$numPages > 1)
                                {
                                    break;
                                }
                                else
                                {
                                    $this->layout = $line[$key+2];
                                }


                                break;
                            case "MP":
                                // 1 pdf only
                                if (count($email->pdfFileNames) !== 1)
                                {
                                    break;
                                }
                                else
                                {
                                    $fileName = implode($email->pdfFileNames);
                                }

                                $path = Storage::disk('processing_pdfs')->path("");

                                //count pages
                                $cmd = 'gs -q -dNODISPLAY -c "('.$path.$fileName.'.pdf) (r) file runpdfbegin pdfpagecount = quit"';
                                exec($cmd, $numPages);

                                //  1 page only
                                if ((int)$numPages[0] > 1)
                                {
                                    $this->layout = $line[$key+2];
                                }
                                else
                                {
                                    break;
                                }

                                break;
                        }
                    }
                }

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
                $retVal = false;
            }
            else
            {
                $handle = fopen(env('APMAILER_PDF2XL_PDF') . $outputFile . '.csv', 'r');

                while ($row = fgets($handle))
                {
                    foreach ($fieldsToCheck as $fieldName => $fieldType)
                    {
                        if (str_contains($row, $fieldName))
                        {
                            $strArray = str_getcsv($row, '|');
                            $key = array_search($fieldName, $strArray);

                            if ($strArray[$key + 1] !== '')
                            {
                                $retVal = true;
                            }
                            else
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


    public static function dailyEmailCount()
    {
        $yesterday = Carbon::now()->subDay()->toDateString();
        $logFile = storage_path('logs/ap_mailer-' . $yesterday . '.log');
        $fn = fopen($logFile, "r");
        $stats = array();
        $hourlyStats = array();
        $hour = 0;

        while (!feof($fn))
        {
            $row = trim(fgets($fn));
            $foo = strpos($row, '##stats##');
            $time = 0;

            if (!$foo === false)
            {
                list(
                    ,
                    $time,
                    $stats['found'],
                    $stats['processed'],
                    $stats['forwarded'],
                    $stats['failed']
                    ) = explode('/', $row);
            }
            else
            {
                continue;
            }

            list($time) = explode(':', $time);

            if ($hour == 0 && $hour != $time)
            {
                $hour = $time;
            }
            elseif ($time != $hour)
            {
                ++$hour;
            }

            foreach ($stats as $index => $value)
            {
                if (array_key_exists($hour, $hourlyStats))
                {
                    if (array_key_exists($index, $hourlyStats[$hour]))
                    {
                        $hourlyStats[$hour][$index] += $value;
                    }
                    else
                    {
                        $hourlyStats[$hour][$index] = $value;
                    }
                }
                else
                {
                    $hourlyStats[$hour][$index] = $value;
                }
            }
        }

        foreach ($hourlyStats as $hour => $data)
        {
            Log::channel('ap_mailer_traffic')->info("$yesterday/$hour/" . implode('/', $data));
        }
    }

    private function forward($email, $moveMail = true)
    {
        if ($this->apRules["to"])
        {
            $cc = $this->apRules["to"]."@ipipes.com";
        }
        else
        {
            $cc = "";
        }
        $to = env("AP_MAIL_FORWARD_TO");

        $this->logIt("info", ["Forwarding email", "To: ".$to, "Cc: ".$cc]);

        $emailArg = ['to' => $to, 'from' => null, 'subject' => null, 'cc' => $cc];

        $sent = $email->forwardEmail($emailArg);

        $this->log->info("Email forwarded: " . json_encode($sent));

        if (!$sent)
        {
            $message = "There has been an error with forwarding an email." . PHP_EOL .
                "Email failed to forward." . PHP_EOL .
                "SUBJECT: " . $email->getHeader('subject') . PHP_EOL .
                "FROM: " . $email->getHeader('from') . PHP_EOL .
                "TIME OF ERROR: " . Carbon::now('America/Chicago') . PHP_EOL;;
            mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure to forward email', $message . PHP_EOL);
            $email->moveMail('failed', array($email->getUID()));
            $retVal = false;
        }
        else
        {
            $retVal = true;
            if ($moveMail)
            {
                $email->moveMail('processed', array($email->getUID()));
            }
        }

        $this->log->info("Email moved to processed folder");

        return $retVal;
    }

    private function logIt($whichLog, array $logData = null)
    {

        switch ($whichLog)
        {
            case "layout_using":
                $this->log->info("Layout: ".$logData["layout"]);
                break;
            case "too_many_cooks":
                $this->log->error("Email skipped due to too many from addresses.");
                $this->log->info("Subject: ".$logData["subject"]);
                break;
            case "pdf_not_found":
                $this->log->info("No PDF found.");
                $this->log->info("Forwarding and continuing");
                break;
            case "csv_sent":
                $this->log->info("Sending csv to prelude");
                $this->log->info("csv sent: " . $logData["sent"]);
                break;
            case "pdf_deleted":
                $this->log->info("Deleting pdf from: ".$logData["pdf_full_file_path"]);
                $this->log->info("pdf delted: ".json_encode($logData["deletePDF"]));
                break;
            case "pdf_moved":
                $this->log->info("Moving pdf to: ".$logData["copyTo"]);
                $this->log->info("pdf moved: ".json_encode($logData["movePDF"]));
                break;
            case "pdf_saved":
                $this->log->info("pdfs saved: ". implode("|",$logData["pdfFileNames"]));
                $this->log->info("Saved to: ".$logData["tmp"]);
                break;
            case "mailer_stuck":
                $this->log->critical("AP Mailer appears to be stuck...attempting to fix.");
                $this->log->info("FORWARD EMAIL - (PURGED)");
                $this->log->info("EMAIL FORWARDED: " . json_encode($logData["sent"]));
                break;
            case "email_start":
                $this->log->info("====================");
                $this->log->info("Email #" . ($logData["key"] + 1) . " of " . $logData["numEmails"]);
                $this->log->info("Subject: " . $logData["emailSubject"]);
                $this->log->info("From: " .  $logData["emailFrom"]);
                break;
            case "AP_rules_not_found":
                $this->log->error("There was an error reading the Aprules folder, unable to find file.");
                break;
            case "pdf_failed_merge":
                $this->log->error("There was an error while executing AP rule ".$logData["m2"]." on this file.");
                break;
            case "csv_missing":
                $this->log->error("Failed to find .csv file for processed document.");
                $this->log->error("Original filename: ".$logData["originalFn"]);
                $this->log->error("Filename: ".$logData["fileName"]);

                $message = "There has been an error processing a pdf through PDF2XL." . PHP_EOL .
                    "No .csv file found after processing through PDF2XL";
                mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure to forward email', $message . PHP_EOL);
                break;
            case "info":
                foreach ($logData as $datum)
                {
                    $this->log->info($datum);
                }
                break;

        }

    }

    private function repeating ($emailUID, $emailSubject)
    {
        $retVal = false;

        if (Cache::has('processing'))
        {
            if ($emailUID == Cache::get('processing'))
            {
                $retVal = true;
                Cache::forget('processing');
                mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Mailer attempted to auto fix',"Subject: " . "$emailSubject");
            }
            else
            {
                Cache::forever('processing', $emailUID);
            }
        }
        else
        {
            Cache::forever('processing', $emailUID);
        }

        return $retVal;
    }

    private function prepend($string, $orig_full_path)
    {
        $context = stream_context_create();
        $orig_file = fopen($orig_full_path, 'r', 1, $context);

        $temp_filename = tempnam(sys_get_temp_dir(), 'php_prepend_');
        file_put_contents($temp_filename, $string);
        file_put_contents($temp_filename, $orig_file, FILE_APPEND);

        fclose($orig_file);
        unlink($orig_full_path);
        rename($temp_filename, $orig_full_path);
    }

}
