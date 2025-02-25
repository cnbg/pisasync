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
        $user = User::where('created', false)
            ->orWhere('pdpa', false)
            ->orderBy('school_id')
            ->orderBy('grade')
            ->orderBy('class_name')
            ->first();

        if ($user) {
            if (!$user->created) {
                $cl = $this->createClass($user);
                if ($cl) {
                    $this->createStudent($user);
                }
            } else if (!$user->pdpa) {
                $this->updatePdpa($user);
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

    private function createClass($user): bool
    {
        $url = env('CEREBRY_URL');
        $jwt = env('CEREBRY_JWT');

        $cr = Http::withHeaders(['jwt-token' => $jwt])->post("$url/school/$user->school_id/create_class/");

        if ($cr->failed()) {
            $err = $cr->json('non_field_errors.0');
            if (str_contains(mb_strtolower($err), 'already exists for school')) {
                return true;
            } else {
                $user->update(['created' => false]);
                $user->update(['create_error' => $cr->body()]);
            }
        } else {
            if ($cr->json('status') === 'created') {
                return true;
            } else {
                $user->update(['created' => false]);
                $user->update(['create_error' => $cr->body()]);
            }
        }

        return false;
    }

    private function createStudent($user)
    {
        $url = env('CEREBRY_URL');
        $jwt = env('CEREBRY_JWT');

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

    private function updatePdpa($user)
    {
        $url = env('CEREBRY_URL');
        $jwt = env('CEREBRY_JWT');

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
}
