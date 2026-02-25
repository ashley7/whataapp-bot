<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('gasol_bots', function (Blueprint $table) {
            $table->id();
            $table->string('phone')->unique();
            $table->string('state'); 
            $table->string('meter_number')->nullable();
            $table->integer('amount')->nullable();
            $table->timestamps();
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('gasol_bots');
    }
};
