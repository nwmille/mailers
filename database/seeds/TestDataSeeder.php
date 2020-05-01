<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{

    public function run()
    {
        $foo = DB::connection('test')->table('workflow');
        $rowsToWrite = 60;
        $x = 0;

        $tableConfig = array(
            'Check_Info_ID' => 'int',
            'Document_ID' => 'int',
            'Vendor_Number' => 'varchar.8',
            'Vendor_Name' => 'varchar.48',
            'Check_Number' => 'varchar.50',
            'Check_Date' => 'date',
            'Check_Amount' => 'int',
            'Check_Processing_Complete' => 'date',
            'Check_Delete_Pending' => 'bool',
            'Check_Flag' => 'bool',
            'Bank_Code' => 'varcahr.10',
        );

        do
        {
            $foo->insert([
                'document_name' => Str::random(9) . '.pdf',
                'order_number' => random_int(399382, 999999),
                'order_date' => Carbon::parse(random_int(2013, 2018) . '-' . random_int(1, 12) . '-' . random_int(1, 30)),
                'customer_number' => random_int(1, 9999),
                'customer_po' => random_int(14848, 99999),
                'scan_date' => Carbon::parse(random_int(2013, 2018) . '-' . random_int(1, 12) . '-' . random_int(1, 30)),
                'batch' => Str::random(9) . 'pdf',
                'order_amount' => random_int(0, 2000000) . '.' .random_int(0, 99)
            ]);
            $x++;
        }while($x != $rowsToWrite);
    }
}
