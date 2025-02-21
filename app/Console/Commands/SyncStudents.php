<?php

namespace App\Console\Commands;

use App\Jobs\SyncStudent;
use Illuminate\Console\Command;

class SyncStudents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'st:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync pisa students data to external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SyncStudent::dispatch();
    }
}
