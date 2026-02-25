<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GasolBot extends Model
{
    use HasFactory;
    
    protected $fillable = ['phone','state','meter_number','amount'];
}
