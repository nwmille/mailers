<?php

namespace App;

use Carbon\Carbon;
use http\Env\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\utility;
use Illuminate\Database\Eloquent\SoftDeletes;




class Invoice extends Model
{
    use SoftDeletes;
    public $timestamps = true;
    public $guarded = ['id'];
    private $invoiceCreate = array (
        'invoice_number' => "",
        'po_number' => "",
        'vendor_id' => "",
        'vendor_name' => "",
        'gross_total' => "",
        'file_name' => "",
        'file_dir' => "",
        'post_date' => "",
        'due_date' => ""
    );
    protected $log;


    public function __construct($invoiceKey = null)
    {
        parent::__construct();

        if ($invoiceKey != null)
        {
            $this->mapInvoice($invoiceKey);
        }
    }


    public function checks()
    {
        return $this->belongsToMany(Check::class);
    }


    public function mapInvoice($invoiceKey)
    {
        $retVal = false;
        list($this->invoiceCreate['vendor_name'], $this->invoiceCreate['vendor_id'], $this->invoiceCreate['invoice_number']) = explode('!', $invoiceKey);

        $alreadyExist = Invoice::where('invoice_number', $this->invoiceCreate['invoice_number'])->where('vendor_id', $this->invoiceCreate['vendor_id'])->first();

        if ($alreadyExist === null)
        {
            try
            {
                $invoiceData = UniData::udReadRec($invoiceKey, "oap.hist");
                $invoiceData = (json_decode($invoiceData, true));


                foreach ($invoiceData as $key => $attributeData)
                {
                    $attributeKey = key($attributeData);
                    $attributeValue = $attributeData[$attributeKey];

                    switch ($attributeKey)
                    {
                        case "1":
                            if ($this->invoiceCreate['vendor_id'] == "00000M")
                            {
                                $this->invoiceCreate['vendor_name'] = $attributeValue;
                            } else
                            {
                                $vendor = self::retrieveVendor("100" . $this->invoiceCreate['vendor_id']);
                                $this->invoiceCreate['vendor_name'] = $vendor["name"];
                            }
                            break;
                        case "3":
                            $this->invoiceCreate['post_date'] = UniData::unidateConvert($attributeValue);
                            break;
                        case "4":
                            $this->invoiceCreate['due_date'] = UniData::unidateConvert($attributeValue);
                            break;
                        case "5":
                            $this->invoiceCreate['gross_total'] = number_format(($attributeValue / 100), 2);
                            break;
                        case "14":
                            $this->invoiceCreate['po_number'] = $attributeValue;
                            break;
                        default:
                            break;
                    }

                }
            } catch (\ErrorException $e)
            {
                Log::info($e);
                Log::info($invoiceData);
                Log::info($invoiceKey);
            }

            $this->fill($this->invoiceCreate);
            $this->save();
            $retVal = true;

        }

        return $retVal;

    }

    public static function checkForDeleted()
    {
        $files_to_delete = Storage::disk('unidata_invoices_deleted')->allFiles();

        if(!$files_to_delete)
        {
            self::logIt("none_found");
            exit();
        }

        foreach ($files_to_delete as $fName)
        {
            if (stripos($fName, ".csv"))
            {
                $pdf_fname = str_replace('csv', 'pdf', $fName);
                $pdf_fname_path = Storage::disk('public')->path(env("APMAILER_PROCESSED_PDF").$pdf_fname);
//                $foo = Storage::disk('checkflow')->path(Carbon::now()->year.'/'.Carbon::now()->month.'/'.$pdf_fname);

                if (file_exists($pdf_fname_path))
                {
                    Storage::disk('public')->delete(env("APMAILER_PROCESSED_PDF").$pdf_fname);
                }

            }
            elseif (stripos($fName, "!"))
            {
                list(, $vendor_id, $invoice_number) = utility::explodeFileKey($fName);
                Invoice::where('invoice_number', $invoice_number)->where('vendor_id', $vendor_id)->delete();
            }

            Storage::disk('unidata_invoices_deleted')->delete($fName);

        }

    }



    public static function postedInvoices()
    {

        try
        {
            $invoices = Storage::disk('unidata_invoices_posted')->allFiles();

            if(!$invoices)
            {
                self::logIt("none_found");
                exit();
            }

            $stackOfInvoices = array_map(
                function ($a)
                {
                    $data_array[] = $a;
                    $file_stream = Storage::disk('unidata_invoices_posted')->readStream($a);
                    while(! feof($file_stream))  {
                        $data_array[] = trim(fgets($file_stream));
                    }

                    return $data_array;
                },
                $invoices);


            if (sizeof($stackOfInvoices) === 0)
            {
                exit();
            }

            self::logIt("starting_process", ["stackOfInvoices" => $stackOfInvoices]);

            foreach ($stackOfInvoices as $invoice)
            {
                $fname = $invoice[0];
                $invoiceKey = $invoice[1];
                $workflow_trigger = false;
                if(count($invoice) == 3)
                {
                    $workflow_trigger = $invoice[2];
                }

                Log::channel('ap_fetch_invoices')->info("=======".$invoiceKey."=======");

                $invoice_doc = $fname;
                $fname = str_replace('csv', 'pdf', $fname);
                $invoiceData = UniData::udReadRec($invoiceKey, "oap.hist");
                $invoiceData = (json_decode($invoiceData, true));

                if (empty($invoiceData) || count($invoiceData) == 0)
                {
                    self::logIt("no_invoice_data", ["invoiceKey"=>$invoiceKey, "fname"=>$fname]);
                    $message = "There has been an error with the apmailer:fetch_invoices process.".PHP_EOL;
                    $message .= "No data was returned when checking the oap.hist file.".PHP_EOL;
                    $message .= "Invoice: ".$invoiceKey.PHP_EOL;
                    $message .= "Filename: ".$fname.PHP_EOL;
                    mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure while posting invoice.', $message);
                    Storage::disk('unidata_invoices_posted')->delete($invoice_doc);
                    continue;
                }

                //remove whitespace and slashes
                $invoiceKey = preg_replace('/\s+|\//', '', $invoiceKey);

                self::logIt("invoice_deets", ["fname" => $fname, "invoiceKey" => $invoiceKey]);

                $newInvoice = new Invoice();

                list($newInvoice->invoiceCreate['vendor_name'], $newInvoice->invoiceCreate['vendor_id'], $newInvoice->invoiceCreate['invoice_number']) = explode('!', $invoiceKey);

                foreach ($invoiceData as $key => $attributeData)
                {
                    $attributeKey = key($attributeData);
                    $attributeValue = $attributeData[$attributeKey];

                    switch ($attributeKey)
                    {
                        case "1":
                            if ($newInvoice->invoiceCreate['vendor_id'] == "00000M")
                            {
                                $newInvoice->invoiceCreate['vendor_name'] = $attributeValue;
                            }
                            else
                            {
                                $vendor = self::retrieveVendor("100" . $newInvoice->invoiceCreate['vendor_id']);
                                $newInvoice->invoiceCreate['vendor_name'] = $vendor["name"];
                            }
                            break;
                        case "3":
                            $newInvoice->invoiceCreate['post_date'] = UniData::unidateConvert($attributeValue);
                            break;
                        case "4":
                            $newInvoice->invoiceCreate['due_date'] = UniData::unidateConvert($attributeValue);
                            break;
                        case "5":
                            $newInvoice->invoiceCreate['gross_total'] = number_format(($attributeValue / 100), 2);
                            break;
                        case "14":
                            $newInvoice->invoiceCreate['po_number'] = $attributeValue;
                            break;
                        default:
                            break;
                    }

                }

                if(Storage::disk('public')->exists(env("APMAILER_PROCESSED_PDF").$fname))
                {
                    //move to checkflow
                    $fileMoved = self::sendToCF($fname, $invoiceKey);

                    if ($fileMoved)
                    {
                        self::logIt("file_move_success", ["invoiceKey"=>$invoiceKey, "fname"=>$fname]);
                        $newInvoice->invoiceCreate['file_name'] = $invoiceKey.'.pdf';
                        $newInvoice->invoiceCreate['file_dir'] = $fileMoved;
                    }
                    else
                    {
                        self::logIt("file_move_fail", ["invoiceKey"=>$invoiceKey, "fname"=>$fname]);
                    }

                    //move to workflow
                    if ($workflow_trigger)
                    {
                        $wfStored = Invoice::sendToWF($workflow_trigger, $fname);

                        if ($wfStored)
                        {
                            self::logIt("wf_move_success");
                        }
                        else
                        {
                            self::logIt("wf_move_fail");
                        }
                    }
                }
                else
                {
                    self::logIt("pdf_not_found", ["invoiceKey"=>$invoiceKey, "fname"=>$fname]);
                }

                //check db for new invoice before writing
                $alreadyExist = Invoice::where('invoice_number', $newInvoice->invoiceCreate['invoice_number'])->where('vendor_id', $newInvoice->invoiceCreate['vendor_id'])->first();

                if ($alreadyExist == null)
                {

                    $newInvoice->fill($newInvoice->invoiceCreate);
                    $saved = $newInvoice->save();

                    if ($saved)
                    {
                        self::logIt("invoice_created", ["invoiceKey" => $invoiceKey]);
                    }
                    else
                    {
                        self::logIt("invoice_failed", ["invoiceKey" => $invoiceKey]);
                    }

                }else
                {
                    $old_fname = $alreadyExist->file_name;
                    $alreadyExist->update(['file_name'=>$newInvoice->invoiceCreate['file_name']]);
                    self::logIt("invoice_update", ["invoice_number"=>$invoiceKey, "vendor_id"=>$newInvoice->invoiceCreate['vendor_id']]);
                    // DELETE OLD FILE FROM CF? OR JUST RENAME WITH file_anme.pdf.old ??
                }

                $deleteCSV = Storage::disk('unidata_invoices_posted')->delete($invoice_doc);
                $deletePDF = Storage::disk('public')->delete(env("APMAILER_PROCESSED_PDF").$fname);

            }

            self::logIt("ending_process");

        }
        catch (\ErrorException $e)
        {
            $message = "There has been an error with the apmailer:fetch_invoices process." . PHP_EOL .
                $e;
            mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure while posting invoice.', $message);
        }
    }


    public static function sendToCF($fileName, $invoiceKey)
    {
        $PDFtempStorage = Storage::disk('public')->path(env("APMAILER_PROCESSED_PDF") . $fileName);
        $checkflow_path = Storage::disk('checkflow')->path(Carbon::now()->year."/".Carbon::now()->month);

        $retval = false;

        if (!is_dir($checkflow_path))
        {
            mkdir($checkflow_path, 0777, true);
        }
        $checkflow_path = $checkflow_path."/".$invoiceKey . '.pdf';

        self::logIt("checkflow_storage", ["checkflow_path" => $checkflow_path]);

        $retval = copy($PDFtempStorage, $checkflow_path);

        if ($retval)
        {
            $retval = $checkflow_path;
        }

        return $retval;
    }


    private static function sendToWF($workflow_fname, $fn)
    {
        $PDFtempStorage = Storage::disk('public')->path(env("APMAILER_PROCESSED_PDF").$fn);
        $TIFtempStorage = Storage::disk('public')->path(env("APMAILER_PROCESSED_PDF").str_replace('pdf', 'tif', $fn));

        $WFstorage = env("WF_DIR_VI").$workflow_fname.".TIF";
        $wf_server_connect = ['address'=>env('WF_ADDRESS'), 'un'=>env('WF_UN'), 'pw'=>env('WF_PW') ];

        //convert pdf to tiff for workflow via ghostscript program
        exec("gs -dNOPAUSE -r300 -sDEVICE=tiffscaled24 -sCompression=lzw -dBATCH -sOutputFile=\"$TIFtempStorage\" \"$PDFtempStorage\"", $output);

        if(file_exists($TIFtempStorage))
        {
            $moved = utility::SFTPmoveFiles($WFstorage, $TIFtempStorage, $wf_server_connect);

            if ($moved)
            {
                unlink($TIFtempStorage);
            }

            self::logIt("ghost_script_success", ['PDFtempStorage'=>$PDFtempStorage, 'TIFtempStorage'=>$TIFtempStorage, 'output'=>$output]);
            $retVal = true;
        }
        else
        {
            self::logIt("ghost_script_failed", ['PDFtempStorage'=>$PDFtempStorage, 'TIFtempStorage'=>$TIFtempStorage, 'output'=>$output]);
            $retVal = false;
        }

        return $retVal;
    }


    public static function retrieveVendor($id)
    {
        $vendorData = UniData::udReadRec($id, 'VEND');
        $vendorData = (json_decode($vendorData, true));
        $vendor["name"] = ($vendorData[0][1]);
        return $vendor;
    }


    private static function logIt($whichLog, array $logData = null)
    {
        $log = Log::channel('ap_fetch_invoices');

        switch ($whichLog)
        {
            case "none_found":
                $log->info("0 INVOICES FOUND");
                $log->info("APPLICATION NORMAL EXIT");
                break;
            case "starting_process":
                $log->info("****************START***************");
                $log->info('FOUND ' . count($logData["stackOfInvoices"]) . ' INVOICES. PROCESSING....');
                break;
            case "invoice_deets":
                $log->info("INVOICE INFO");
                $log->info("Processing file: ".$logData["fname"]);
                $log->info("Processing invoice key: ". $logData["invoiceKey"]);
                break;
            case "no_invoice_data":
                $log->error("Invoice failed to return any valid data.");
                $log->error("Invoice: ".$logData["invoiceKey"]);
                $log->error("Filename: ".$logData["fname"]);
                $log->error("Skipping and continuing.");
                break;
            case "file_move_fail":
                $log->error("FAILURE TO MOVE INVOICE TO CHECKFLOW");
                $message = "There has been an error with apmailer:fetch_invoices.". PHP_EOL .
                    "Action required. Please consult the documentation, reference error code: REF#0003. ". PHP_EOL .
                    "Reason: failure to move pdf file". PHP_EOL .PHP_EOL .
                    "Invoice number: ". $logData["invoiceKey"] . PHP_EOL .
                    "File name: ". $logData["fname"] . PHP_EOL .
                    "http://wiki.ipipes.com/index.php/Mailer#REF.230003";
                mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure to move invoice to Checkflow', $message);
                break;
            case "file_move_success":
                $log->info("File moved to Checkflow");
                break;
            case "checkflow_storage":
                $log->info("Checkflow path: ".$logData["checkflow_path"]);
                break;
            case "ghost_script_success":
                $log->info("FILE CONVERTED TO .tif CONFIRMED");
                $log->info("File in: ".$logData["PDFtempStorage"]);
                $log->info("File out: ".$logData["TIFtempStorage"]);
                $log->info("gs script stdout: ");
                foreach($logData["output"] as $line)
                {
                    $log->info($line);
                }
                break;
            case "ghost_script_failed":
                $log->error("COULD NOT FIND .tif FILE AFTER CONVERSION");
                $log->error("File in: ".$logData["PDFtempStorage"]);
                $log->error("File out: ".$logData["TIFtempStorage"]);
                $log->error("gs script stdout: ".$logData["output"]);
                break;
            case "wf_move_success":
                $log->info("File moved to WF");
                break;
            case "wf_move_fail":
                $log->error("FAILURE TO MOVE INVOICE TO WORKFLOW");
                $message = "There has been an error with apmailer:fetch_invoices.". PHP_EOL .
                    "Action required. Please consult the documentation, reference error code: REF#0005. ". PHP_EOL .
                    "Reason: failure to move pdf file to WF". PHP_EOL .PHP_EOL .
                    "Invoice number: ". $logData["invoiceKey"] . PHP_EOL .
                    "File name: ". $logData["fname"] . PHP_EOL .
                    "http://wiki.ipipes.com/index.php/Mailer#REF.230005";
                mail('nmiller@ipipes.com', 'APmailer ***ERROR*** Failure to move invoice to wf', $message);
                break;
            case "invoice_created":
                $log->info("INVOICE CREATED");
                $log->info("Invoice Number: ".$logData["invoiceKey"]);
                break;
            case "invoice_failed":
                $log->error("FAILURE TO SAVE INVOICE");
                $log->error("Invoice Number: ".$logData["invoiceKey"]);
                break;
            case "invoice_update":
                $log->info("PREVIOUS DATABASE ENTRY FOUND FOR INVOICE:");
                $log->info("Invoice number:".$logData["invoice_number"]);
                $log->info("Vendor id:".$logData["vendor_id"]);
                $log->info("Updating pdf only");
                break;
            case "pdf_not_found":
                $log->error("FAILURE TO FIND PDF");
                $log->error("Invoice Number: ".$logData["invoiceKey"]);
                break;
            case "ending_process":
                $log->info("****************END***************");
                break;

        }

    }



}
