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
    public function __construct(
        protected User $user
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $user = $this->user;
        $srv = new StSyncService();

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
        }
    }
}
