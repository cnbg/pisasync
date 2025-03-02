<?php

use App\Http\Controllers as Front;
use Illuminate\Support\Facades\Route;

Route::get('/sync/st/{citizen_id}', [Front\StSyncController::class, 'sync']);
Route::get('/sync/stjob', [Front\SyncController::class, 'stjob']);
Route::get('/sync/pisa', [Front\SyncController::class, 'pisa']);
