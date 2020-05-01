<?php

namespace App;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use phpseclib\Net\SSH2;
use phpseclib\Crypt\RSA;


class Pdf2xl
{

    private $fileIn, $fileOut, $layoutDir;
    public $output, $errorOutput, $rsa;
    static $ssh;
    const DEBUG = false;


    function __construct($in, $out, $layoutDir, $rsa = null)
    {
        $this->fileIn = $in;
        $this->fileOut = $out;
        $this->layoutDir = $layoutDir;
        $this->rsa = $rsa;

        if (self::DEBUG)
        {
            var_dump("===========");
            var_dump("File/class: " . __CLASS__);
            var_dump("===========");
        }

    }

    private function connect()
    {

        Pdf2xl::$ssh = new SSH2('192.168.158.35');
        $key = new RSA();

        //for using a public key rather than un/pw
//        $key->loadKey(file_get_contents($this->rsa));
//        if (Pdf2xl::$ssh->login(env('APMAILER_PDF2XL_USERNAME'), $key))

        if (Pdf2xl::$ssh->login(env('APMAILER_PDF2XL_USERNAME'), env("APMAILER_PDF2XL_PASSWORD")))
        {
            echo "Public Key Authentication Successful\n";

        } else
        {
            Log::error('SSH connection to Windows box failed.');
            die("Public Key Authentication Failed\n");
        }

        if (self::DEBUG)
        {
            var_dump("Method: " . __FUNCTION__);
        }

    }

    public function disconnect()
    {
        // ssh2_connect returns a 'SSH2 Session' resource type
        // check to make sure our resource hasn't been closed
        if (is_resource(Pdf2xl::$connection))
        {
            return ssh2_disconnect(Pdf2xl::$connection);
        }
    }


    public function run(array $pdfFileNames, $layout)
    {
        $processedFiles = array();
        $layout = $this->layoutDir.$layout.'.layoutx';

        if (is_null(Pdf2xl::$ssh) || !Pdf2xl::$ssh->isAuthenticated())
        {
            $this->connect();
        }

        foreach ($pdfFileNames as $pdfFileName)
        {
            $_input = $this->fileIn.$pdfFileName.'.pdf';
            $_output =  $this->fileOut.$pdfFileName.'.csv';


            if (self::DEBUG)
            {
                dump($_input);
                dump($layout);
                dump($_output);
            }

            try
            {
//                "C:\Program Files (x86)\CogniView\PDF2XL\PDF2XL.exe" -input="C:\ips\mailer\ap\storage\pdfs\processing\1586273051.pdf" -layout="C:\ips\APConversionLayouts\test_pipelineexpress.layoutx" -format=csvfile -range=all -output="C:\ips\1570029593.csv" -noui -autoopen=off

                Pdf2xl::$ssh->setTimeout(50);
                $foo = Pdf2xl::$ssh->exec('PDF2XL -input="'.$_input.'" -layout="C:\ips\\' . $layout . '" -format=csvfile -range=all -output="' . $_output . '" -noui -autoopen=off 2> c:\\error.log');
                $output = Pdf2xl::$ssh->exec('IF EXIST '.$_output.' ECHO LUKEUSETHEFORCE');

            }catch (\ErrorException $e)
            {
                Log::error('PDF2XL conversion has failed');
                Log::error('File: '.$pdfFileName);
                Log::error('Layout: '.$layout);
                Log::error('Error: '.$foo);
                Log::error($e);
                $output = 'foo';
            }
            $value = trim($output) == "LUKEUSETHEFORCE" ? true : false;
            $processedFiles[$pdfFileName] = $value;
        }


        return $processedFiles;
    }




}
