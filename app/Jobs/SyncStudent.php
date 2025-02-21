<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

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
        $url = env('CEREBRY_URL');
        $jwt = env('CEREBRY_JWT');

        $user = User::where('created', false)->orWhere('pdpa', false)->first();

        if ($user) {
            if (!$user->created) {
                $cr = Http::withHeaders(['jwt-token' => $jwt])->post("$url/school/$user->school_id/create_student/", $user);

                if ($cr->failed()) {
                    $err = $cr->json('non_field_errors.0');
                    if (str_starts_with($err, 'User already registered with citizen_id')) {
                        $user->update(['created' => true]);
                        $user->update(['create_error' => null]);
                    } else {
                        $user->update(['created' => false]);
                        $user->update(['create_error' => $cr->body()]);
                    }
                } else {
                    if ($cr->json('status') === 'created') {
                        $user->update(['created' => true]);
                        $user->update(['create_error' => null]);
                    } else {
                        $user->update(['created' => false]);
                        $user->update(['create_error' => $cr->body()]);
                    }
                }
            }

            if (!$user->pdpa) {
                $pr = Http::withHeaders(['jwt-token' => $jwt])->post("$url/user/$user->citizen_id/allow-pdpa/", $user);

                if ($pr->failed()) {
                    $err = $pr->json('error');
                    if ($err === 'user has already agreed to pdpa') {
                        $user->update(['pdpa' => true]);
                        $user->update(['pdpa_error' => null]);
                    } else {
                        $user->update(['pdpa' => false]);
                        $user->update(['pdpa_error' => $pr->body()]);
                    }
                } else {
                    if ($pr->json('message') === 'success') {
                        $user->update(['pdpa' => true]);
                        $user->update(['pdpa_error' => null]);
                    } else {
                        $user->update(['pdpa' => false]);
                        $user->update(['pdpa_error' => $pr->body()]);
                    }
                }
            }

            $tr = Http::withHeaders(['jwt-token' => $jwt])->get("$url/user/$user->citizen_id/token/");

            $token = $tr->json('token');
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
