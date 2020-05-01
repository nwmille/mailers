<?php

namespace App\Console\Commands;

use App\Check;
use Illuminate\Console\Command;

class Checks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apmailer:fetch_checks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'fetch checks';

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
        Check::dailyChecks();
    }
}
