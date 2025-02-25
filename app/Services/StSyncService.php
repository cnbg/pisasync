<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class StSyncService
{
    public function createClass($user): bool
    {
        $url = env('CEREBRY_URL');
        $jwt = env('CEREBRY_JWT');

        $req = Http::withHeaders(['jwt-token' => $jwt])
            ->post("$url/school/$user->school_id/create_class/", [
                'grade' => $user->grade,
                'name' => $user->grade . $user->class_name,
            ]);

        if ($req->failed()) {
            $err = $req->json('non_field_errors.0');
            if (str_contains(mb_strtolower($err), 'already exists for school')) {
                return true;
            } else {
                $user->update([
                    'created' => false,
                    'create_error' => $req->body()
                ]);
            }
        } else {
            if ($req->json('status') === 'created') {
                return true;
            } else {
                $user->update([
                    'created' => false,
                    'create_error' => $req->body()
                ]);
            }
        }

        return false;
    }

    public function createStudent($user): bool
    {
        $url = env('CEREBRY_URL');
        $jwt = env('CEREBRY_JWT');

        $req = Http::withHeaders(['jwt-token' => $jwt])
            ->post("$url/school/$user->school_id/create_student/", [
                'citizen_id' => $user->citizen_id,
                'grade' => $user->grade,
                'class_name' => $user->grade . $user->class_name,
            ]);

        if ($req->failed()) {
            $err = $req->json('non_field_errors.0');
            if (str_contains(mb_strtolower($err), 'user already registered')) {
                $user->update([
                    'created' => true,
                    'create_error' => null
                ]);

                return true;
            } else {
                $user->update([
                    'created' => false,
                    'create_error' => $req->body()
                ]);
            }
        } else {
            if ($req->json('status') === 'created') {
                $user->update([
                    'created' => true,
                    'create_error' => null
                ]);

                return true;
            } else {
                $user->update([
                    'created' => false,
                    'create_error' => $req->body()
                ]);
            }
        }

        return false;
    }

    public function updatePdpa($user): bool
    {
        $url = env('CEREBRY_URL');
        $jwt = env('CEREBRY_JWT');

        $req = Http::withHeaders(['jwt-token' => $jwt])
            ->post("$url/user/$user->citizen_id/allow-pdpa/", [
                'allow' => true,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ]);

        if ($req->failed()) {
            $err = $req->json('error');
            if (str_contains(mb_strtolower($err), 'already agreed to pdpa')) {
                $user->update([
                    'pdpa' => true,
                    'pdpa_error' => null
                ]);
                return true;
            } else {
                $user->update([
                    'pdpa' => false,
                    'pdpa_error' => $req->body()
                ]);
            }
        } else {
            if ($req->json('message') === 'success') {
                $user->update([
                    'pdpa' => true,
                    'pdpa_error' => null
                ]);
                return true;
            } else {
                $user->update([
                    'pdpa' => false,
                    'pdpa_error' => $req->body()
                ]);
            }
        }

        return false;
    }

    public function getToken($user)
    {
        $url = env('CEREBRY_URL');
        $jwt = env('CEREBRY_JWT');

        $resp = Http::withHeaders(['jwt-token' => $jwt])
            ->get("$url/user/$user->citizen_id/token/");

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
    }
}
