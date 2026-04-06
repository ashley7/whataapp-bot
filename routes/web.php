<?php

use App\Http\Controllers\GasolBotController;
use Illuminate\Support\Facades\Route;

 
//Whatsapp endpoints
Route::post('whatsapp_webhook', [GasolBotController::class, 'handle']);
Route::get('whatsapp_webhook', [GasolBotController::class, 'webhook']);
Route::get('send_whats_app_message', [GasolBotController::class, 'sendWhatsAppMessage']);


Route::get('url',[GasolBotController::class,'url']);

Route::post('register_phone_number',[GasolBotController::class,'registerPhoneNumber']);

Route::get('/', function () {
    return "This is a whatsapp bot setup ..";
});
