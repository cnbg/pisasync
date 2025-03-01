<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

class SyncController extends Controller
{
    public function pisa()
    {
        return DB::connection('moncon')
            ->table('STUDENT')
            ->limit(10)
            ->get();
    }
}
