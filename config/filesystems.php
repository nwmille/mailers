<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3", "rackspace"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'PDF2XL' => [
            'driver' => 'local',
            'root' => storage_path('app/public/PDF2XL'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'APindex_pdfs' => [
            'driver' => 'local',
            'root' => storage_path('app/public/APindex/pdf'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            ],

        'checkflow' => [
            'driver' => 'local',
            'root' => storage_path('app/public/Checkflow'),
            'url' => env('APP_URL').'/storage/app/public/Checkflow',
            'visibility' => 'public',
        ],

        //--Accounts Payable system--//
        'processing_pdfs' => [
            'driver' => 'local',
            'root' => storage_path('app/public/PDF2XL/mailer/ap/storage/pdfs/processing'),
            'url' => env('APP_URL').'/storage/APmailer/pdfs/processing/',
            'visibility' => 'public',
        ],

        'processed_pdfs' => [
            'driver' => 'local',
            'host' => 'local',
            'root' => storage_path('app/public/APmailer/pdfs/post'),
            'url' => env('APP_URL').'/storage/app/public/APmailer/pdfs/post',
            'visibility' => 'public',
        ],

        'apmailer_unidata' => [
            'driver' => 'ftp',
            'host' => '192.168.168.50',
            'username' => 'ipsit',
            'password' => '!ps606',
            'root' => '/ud/tmp/payables/auto/',
        ],

        'apmailer_unidata_TEST' => [
            'driver' => 'ftp',
            'host' => '192.168.168.50',
            'username' => 'ipsit',
            'password' => '!ps606',
            'root' => '/ud/tmp/payables/test/',
        ],

        'workflow_vendor_invoice' => [
            'driver' => 'ftp',
            'host' => '192.168.158.235',
            'username' => 'administrator',
            'password' => '123!p!pes456',
            'root' => '/opt/ips/wf/tulsa/split/VendorInvoice',
        ],

        'ftp_unidata' => [
            'driver' => 'ftp',
            'host' => '192.168.168.50',
            'username' => 'ipsit',
            'password' => '!ps606',
            'root' => '/ud/tmp/auto_import/',
        ],

        'unidata_invoices_posted' => [
            'driver' => 'ftp',
            'host' => '192.168.168.50',
            'username' => 'ipsit',
            'password' => '!ps606',
            'root' => '/ud/tmp/payables/posted',
        ],
        'unidata_invoices_deleted' => [
            'driver' => 'ftp',
            'host' => '192.168.168.50',
            'username' => 'ipsit',
            'password' => '!ps606',
            'root' => '/ud/tmp/payables/deleted',
        ],

        'unidata_ap_rules' => [
            'driver' => 'ftp',
            'host' => '192.168.168.50',
            'username' => 'ipsit',
            'password' => '!ps606',
            'root' => '/ud/tmp/payables/rules',
        ],

        'unidata_checks' => [
            'driver' => 'ftp',
            'host' => '192.168.168.50',
            'username' => 'ipsit',
            'password' => '!ps606',
            'root' => '/ud/tmp/payables/checks',
        ],

        //--END--//

        //--Quote System--//
        'public_quoteMailer' => [
            'driver' => 'local',
            'root' => storage_path('app/public/QuoteMailer'),
            'url' => env('APP_URL').'/storage/QuoteMailer',
            'visibility' => 'public',
        ],
        //--END--//


        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

    ],

];
