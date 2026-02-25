<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GasolBot extends Model
{
    use HasFactory;

    protected $fillable = ['phone','state','meter_number','amount'];

    public static function keyWords()  {

        $conversationStarters = [
            'hi',
            'hay',
            'hey',
            'gasol',            
            'gass',
            'hello',
            'hey',
            'yo',
            'yoh',
            'ola',
            'mambo',
            'start',
            'menu',
            'help',
            'gas',
            'buy',
            'buy gas',
            'token',
            'gas token',
            'purchase',
            'pay',
            'payment',
            'balance',
            'check',
            'services',
            'options',
            'info',
            'information',
            'support',
            'contact',
            'good morning',
            'good afternoon',
            'good evening',
            'gm',
            'ga',
            'ge',
            'how are you',
            'can i buy gas',
            'i want gas',
            'i need gas',
            'i want to buy gas',
            'i need a token',
            'ok',
            'okay',
            'thank you',
            'thanks',
            'good',
        ];

        return $conversationStarters;
            
    }
}
