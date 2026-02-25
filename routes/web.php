<?php

use App\Http\Controllers\GasolBotController;
use Illuminate\Support\Facades\Route;

 
//Whatsapp endpoints
Route::post('whatsapp_webhook', [GasolBotController::class, 'handle']);
Route::get('whatsapp_webhook', [GasolBotController::class, 'webhook']);

Route::get('/', function () {
    return view('welcome');
});
