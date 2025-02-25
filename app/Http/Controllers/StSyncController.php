<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\StSyncService;

class StSyncController extends Controller
{
    public function __construct(
        protected StSyncService $srv
    )
    {
    }

    public function sync($citizen_id)
    {
        $user = User::where('citizen_id', $citizen_id)->first();

        if ($user) {
            if (!$user->created) {
                if ($this->srv->createClass($user)) {
                    $this->srv->createStudent($user);
                }
            }

            if ($user->created && !$user->pdpa) {
                $this->srv->updatePdpa($user);
            }

            if ($user->created && $user->pdpa) {
                $this->srv->getToken($user);
            }
        }

        return $user;
    }
}
