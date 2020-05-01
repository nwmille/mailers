<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Mockery\Exception;
use SoapClient;

class UniData extends Model
{
    public static function unidateConvert($unidays)
    {
        $uniDay = Carbon::create(1967, 12, 31);
        return $uniDay->addDays($unidays)->toDateString();
    }

    public static function udReadRec($key, $file)
    {

        $url = "http://xxxxxxxx/xxxxxx/xx?xx";
        $foo = new SoapClient($url);
        $parameters = array(
            "key" => $key,
            "file" => $file
        );

        try
        {
            $fooObj = $foo->udReadRec($parameters);
            $retVal = $fooObj->return;
//            $retVal = json_decode($retVal, true);


        }catch (Exception $e)
        {
//            $error = "";
            $retVal = false;
        }


        return $retVal;
    }


}
