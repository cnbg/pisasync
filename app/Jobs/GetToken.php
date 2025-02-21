<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class GetToken implements ShouldQueue
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
        $url = env('CEREBRY_URL');
        $jwt = env('CEREBRY_JWT');

        $user = User::whereNull('token')->whereNotNull('create_error')->first();

        if ($user) {
            $resp = Http::withHeaders(['jwt-token' => $jwt])->get("$url/user/$user->citizen_id/token/");

            $token = $resp->json('token');
            if (strlen($token) > 100 && str_starts_with($token, 'ey')) {
                $user->update([
                    'token' => $token,
                    'created' => true,
                    'create_error' => null,
                    'pdpa' => true,
                    'pdpa_error' => null,
                ]);
            }

            self::dispatch();
        }
    }
}
