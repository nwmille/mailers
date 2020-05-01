<?php

namespace App\Http\Controllers\Checkflow;

use App\Invoice;
use App\Check;
use App\UniData;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use phpDocumentor\Reflection\Types\Array_;


class Checkflow extends Controller
{
    public function foo(Request $request)
    {
        $baz = $request->all();
        if($baz['email'] === 'testing' && $baz['password'] === 'passw0rd')
        {
            return view('checkflow');
        }
    }


    public static function searchChecks(Request $request)
//    public static function searchChecks()
    {
        $input = $request->all();

        $buildQuery = Check::select('*');

        foreach ($input as $fieldName => $value)
        {
            if ($value)
            {
                switch ($fieldName)
                {
                    case 'check_number':
                        $buildQuery = $buildQuery->where($fieldName, $value);
                        break;
                    case 'vendor_name':
                        $buildQuery = $buildQuery->where($fieldName, 'like', '%'.$value.'%');
                        break;
                    case 'check_date':
                        list($startDate, $endDate) = explode(' to ', $value);
                        $buildQuery = $buildQuery->whereBetween('check_date', [$startDate, $endDate]);
                        break;
                    default:
                        break;
                }
            }
        }

        $queryResults = $buildQuery->get();

        $retVal = array();

        foreach ($queryResults as $index => $check)
        {
            $invoices = $check->invoices()->get();

            foreach ($invoices as $invoice)
            {
                $hasPDF = false;
                if ($invoice->file_name)
                {
                    $hasPDF = true;
                }
                $invoiceValues = array
                (
                    'po_number' => $invoice->po_number,
                    'invoice_total' => $invoice->gross_total,
                    'invoice_number' => array($invoice->invoice_number, $invoice->vendor_id, $hasPDF)
                );

                $retVal[] = array_merge($check->toArray(), $invoiceValues);
            }
        }

        return $retVal;
    }

    public static function searchInvoices(Request $request)
//    public static function searchInvoices()
    {
        $input = $request->all();
        $retVal = array();


//        $buildQuery = Invoice::select('*')->where('invoice_number', '321138');
        $buildQuery = Invoice::select('*');

        foreach ($input as $fieldName => $value)
        {
            if ($value === null)
            {
                continue;
            }

            switch ($fieldName)
            {
                case 'invoice_number':
                    $buildQuery = $buildQuery->where($fieldName, $value);
                    break;
                case 'vendor_name':
                    $buildQuery = $buildQuery->where($fieldName, 'like', '%'.$value.'%');
                    break;
                case 'check_date':
                    list($startDate, $endDate) = explode(' to ', $value);
                    $buildQuery = $buildQuery->whereBetween('check_date', [$startDate, $endDate]);
                    break;
                default:
                    break;
            }
        }


        $queryResults = $buildQuery->get();

        foreach ($queryResults as $index => $invoice)
        {
            $checks = $invoice->checks()->get();

            if (!count($checks))
            {
                $checkValues = array(
                    'po_number' => "N/A",
                    'check_amount' => "N/A",
                    'check_number' => "N/A"
                );

                $retVal[] = array_merge($invoice->toArray(), $checkValues);
            }
            else
            {
                foreach ($checks as $check)
                {

                    $checkValues = array(
                        'po_number' => $invoice->po_number,
                        'check_amount' => $check->check_amount,
                        'check_number' => $check->check_number
                    );

                    $retVal[] = array_merge($invoice->toArray(), $checkValues);
                }

            }


        }

        return $retVal;
    }


    public static function retrieveCheckRun($ckpBatchKey)
    {

        $ckpBatchData = UniData::udReadRec($ckpBatchKey, "CKP.BATCH");
        $ckpBatchData = json_decode($ckpBatchData, true);
        $ckpKeys =  self::getAttribute($ckpBatchData, 15);

        $invoiceKeys = array();

        foreach ($ckpKeys as $ckpKey)
        {
            $ckpData = UniData::udReadRec($ckpKey, "CKP");
            $ckpData = json_decode($ckpData, true);
            $invoices = self::getAttribute($ckpData, 10);

            if (is_array($invoices))
            {
                foreach ($invoices as $invoice)
                {
                    $invoiceKeys[] = $invoice;
                }
            }
            else
            {
                $invoiceKeys[] = $invoices;
            }
        }

        $displayThese = array();

        foreach ($invoiceKeys as $invoiceKey)
        {
            list(, $vendorId, $invoiceNum) = explode('!', $invoiceKey);
            $invoiceData = UniData::udReadRec($invoiceKey, "OAP.HIST");
            $invoiceData = json_decode($invoiceData, true);

            if($vendorId == "00000M")
            {
                $vendorName = self::getAttribute($invoiceData, 1);
            }
            else
            {
                $vendorName = Invoice::retrieveVendor("100".$vendorId);
                $vendorName = $vendorName['name'];
            }

            $displayThese[] = [
                '1' => $invoiceNum,
                '2' => self::getAttribute($invoiceData, 14),
                '3' => $vendorName,
                '4' => UniData::unidateConvert(self::getAttribute($invoiceData, 4)),
                '5' => number_format((self::getAttribute($invoiceData, 5) / 100), 2)
            ];


        }

        $headers = array('Invoice Number', 'PO Number','Vendor', 'Due Date', 'Total');
        $tableName = "Checkrun Invoices";

        return view('table')->with(compact('displayThese', 'headers', 'tableName'));

    }

    public static function getAttribute($jsonData, $attributeNum)
    {
            $jsonData = ($jsonData[$attributeNum - 1][$attributeNum]);
            $attributeData = array();

            if (!is_array($jsonData))
            {
                $attributeData = $jsonData;
            }else
            {
                foreach ($jsonData as $key => $value)
                {
                    foreach ($value as $item)
                    {
                        $attributeData[] = ($item);
                    }
                }
            }

        return $attributeData;
    }


//    public function showPDF($invoiceKey or $checkKey)
//    {
//
//    }



}






