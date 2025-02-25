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
        $users = User::query()
            ->whereNull('token')
            ->where(function ($query) {
                $query->whereNull('try_at');
            })
            ->orderBy('school_id')
            ->orderBy('grade')
            ->orderBy('class_name')
            ->get();

        foreach ($users as $user) {
            dispatch(new SyncStudent($user))->delay(now()->addSecond());
        }
    }
}
