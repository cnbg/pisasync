<?php

namespace App\Console\Commands;

use App\Jobs\SyncStudent;
use App\Models\User;
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
        $user = User::query()
            ->where(function ($query) {
                $query->where('created', false)->orWhere('pdpa', false);
            })
            ->where(function ($query) {
                $query->whereNull('try_at')->orWhere('try_at', '<', now()->subDay());
            })
            ->orderBy('school_id')
            ->orderBy('grade')
            ->orderBy('class_name')
            ->get();

        foreach ($user as $u) {
            SyncStudent::dispatch($u)->delay(now()->addSecond());
        }
    }
}
