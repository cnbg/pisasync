<?php

use App\Http\Controllers\StSyncController;
use Illuminate\Support\Facades\Route;

Route::get('/st/sync/{citizen_id}', [StSyncController::class, 'sync']);
