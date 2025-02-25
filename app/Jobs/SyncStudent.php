<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\StSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncStudent implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $srv = new StSyncService();
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
            ->first();

        if ($user) {
            if (!$user->created) {
                if ($srv->createClass($user)) {
                    $user->created = $srv->createStudent($user);
                }
            }

            if ($user->created && !$user->pdpa) {
                $user->pdpa = $srv->updatePdpa($user);
            }

            if ($user->created && $user->pdpa) {
                $srv->getToken($user);
            }

            $user->update(['try_at' => now()]);

            self::dispatch();
        }
    }
}
