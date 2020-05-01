<?php

namespace App;

use App\Http\Controllers\Checkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\In;

class Check extends Model
{

    public $connection = '';
    public $timestamps = true;
    public $guarded = ['id'];
    private $checkCreate = array (
        'vendor_id' => "",
        'vendor_name' => "",
        'check_number' => "",
        'check_date' => "",
        'check_amount' => "",
        'check_flag' => "",
        'bank_code' => ""
    );

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class);
    }

    public function giveInvoiceTo(Invoice $invoice, array $additionalData = null)
    {
        $retval = "";

        if (is_null($additionalData))
        {
            $retval = $this->invoices()->save($invoice);
        }
        else
        {
            $retval = $this->invoices()->save($invoice, $additionalData);
        }

        return $retval;
    }

    public static function dailyChecks()
    {
        Checkflow\Checkflow::searchChecks();
        exit();
        try
        {
            $checkBook = Storage::disk('unidata_checks')->allFiles();

            foreach ($checkBook as $checkKey)
            {
                $newCheck = new Check();
                $checkData = UniData::udReadRec($checkKey, "APCHECK");
                $checkData = (json_decode($checkData, true));
                list(, $newCheck->checkCreate['bank_code'], $newCheck->checkCreate['check_number']) = explode('!', $checkKey);

                $invoicesPaid = array();

                foreach ($checkData as $key => $attributeData)
                {

                    $attributeKey = key($attributeData);
                    $attributeValue = $attributeData[$attributeKey];

                    switch ($attributeKey)
                    {
                        case "1":
                            $newCheck->checkCreate['vendor_id'] = $attributeValue;
                            break;
                        case "2":
                            $newCheck->checkCreate['check_date'] = UniData::unidateConvert($attributeValue);
                            break;
                        case "3":
                            $newCheck->checkCreate['check_amount'] = number_format(($attributeValue / 100), 2);
                            break;
                        case "30":
                            if ($newCheck->checkCreate['vendor_id'] == "00000M")
                            {
                                $newCheck->checkCreate['vendor_name'] = $attributeValue;
                            }
                            else
                            {
                                $vendor = self::retrieveVendor("100".$newCheck->checkCreate['vendor_id']);
                                $newCheck->checkCreate['vendor_name'] = $vendor["name"];
                            }
                            break;
                        case"37":
                            foreach ($attributeValue as $foo)
                            {
                                $invoicesPaid[] = $foo[key($foo)];
                            }
                            break;
                        default:
                            break;
                    }
                }

                $newCheck->fill($newCheck->checkCreate);
                $newCheck->save();

                foreach ($invoicesPaid as $invoiceKey)
                {
                    list(, , $invoiceNumber) = explode('!', $invoiceKey);
                    $invoice = Invoice::where('invoice_number', $invoiceNumber)
                               ->where('vendor_id', $newCheck->checkCreate['vendor_id'])
                               ->first();

                    if ($invoice === null)
                    {
                        $invoice = new Invoice($invoiceKey);
                    }

                    $newCheck->giveInvoiceTo($invoice);
                }
            }
        }
        catch (\ErrorException $e)
        {
            dump($e);
        }
    }


    public static function retrieveVendor($id)
    {
        $vendorData = UniData::udReadRec($id, 'VEND');
        $vendorData = (json_decode($vendorData, true));
        $vendor["name"] = ($vendorData[0][1]);
        return $vendor;
    }


}
