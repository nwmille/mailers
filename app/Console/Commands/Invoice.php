<?php

namespace App\Console\Commands;

use App\Invoice as floop;
use Illuminate\Console\Command;

class Invoice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoice {option}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fetch invoices that have been posted';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $args= $this->arguments();

        switch ($args['option'])
        {
            case "fetch_invoices":
                floop::postedInvoices();
                break;
            case "check_deleted":
                floop::checkForDeleted();
                break;
        }



    }
}
