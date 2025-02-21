<?php

namespace App\Console\Commands;

use App\Jobs\GetToken;
use Illuminate\Console\Command;

class GetTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'st:token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get tokens from external API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
         GetToken::dispatch();
    }
}
