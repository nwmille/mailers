<?php

namespace App\Console\Commands\QuoteOmatic;

use Illuminate\Console\Command;
use App\Http\Controllers\QuoteOmatic\QuoteMailer as QuoteMailerController;

class QuoteMailer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quotemailer:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the QuoteMailer process.';

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
        new QuoteMailerController();
    }
}
