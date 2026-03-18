<?php

namespace App\Models;

 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; 
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GasolBot extends Model
{
    use HasFactory;
   protected $fillable = ['phone','state','meter_number','amount'];

    public static function keyWords()  {

        $conversationStarters = [
            'hi',
            'hi gasol',
            'hello gasol',
            'hay gasol',
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

    public static function sendText($phone, $message)
    {
         Log::info($message);
        return Http::withToken(config('services.whatsapp.token'))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->post(self::url(), [
                    "messaging_product" => "whatsapp",
                    "to" => $phone,
                    "type" => "text",
                    "text" => [
                        "body" => $message
                    ]
                ]);
    }


    public static function sendHelp($phone)
    {
        $message = "*Gas Token Help*\n\n"
            ."• Buy Gas Token – Purchase gas using our app\n"
            ."• Enter correct meter number\n"
            ."• Tokens are credited instantly after payment\n\n"
            ."📞 Support: +256782033814\n\n"
            ."Type *MENU* to return";

        return self::sendText($phone, $message);
    }

    public static function url()
    {
        return "https://graph.facebook.com/"
            .config('services.whatsapp.version')
            ."/"
            .config('services.whatsapp.phone_id')
            ."/messages";
    }

    public static function validatePhoneNumber($phone)
    {

        $phone_number = "";

        if ($phone[0]=="+") {

        $phone_number=str_replace("+256", "0", $phone);

        }elseif ($phone[0]=="2") {

            $phone_number=str_replace("256", "0", $phone);    

        }else{

            $phone_number=$phone;

        }

        return $phone_number;

    }


    public static function detectUgandaNetwork($phone)
    {

        // Remove spaces, +
        $phone = preg_replace('/\D/', '', $phone);

        // Convert 07xxxxxxxx → 2567xxxxxxxx
        if (strlen($phone) === 10 && str_starts_with($phone, '0')) {
            $phone = '256' . substr($phone, 1);
        }
        
        $mtn = ['25677', '25678', '25676', '25639'];
    
        $airtel = ['25670', '25674', '25675', '25620'];

        foreach ($mtn as $prefix) {
            if (str_starts_with($phone, $prefix)) {
                return 'mtn';
            }
        }

        foreach ($airtel as $prefix) {
            if (str_starts_with($phone, $prefix)) {
                return 'airtel';
            }
        }

        return 'unknown';
    }

    public static function getTransactions($mode,$text){

        // $query = Transaction::with(['payments.stronPayments', 'stronPayments']);

 

        if ($mode === 'RETRIEVE_WITH_METER'){

            // $query->where('transactions.meter_number', $text);

        }

        if ($mode === 'RETRIEVE_WITH_TREF'){

            // $query->where('transactions.transaction_ref', $text);

        }

        if ($mode === 'RETRIEVE_WITH_PHONE_NUMBER') {

            // $customer_mobile_record = DB::table('customers_mobile_mapping')
            //         ->where('mobile_number', $text)
            //         ->first();

            // $customer_mobile_id = $customer_mobile_record ? $customer_mobile_record->id : null;

            // $query->where('transactions.customer_mobile_id', $customer_mobile_id);

        }

        // $transactions = $query->latest('transactions.updated_at')->take(5)->get();
        $transactions=collect();
        $message = "*GAS PAYMENT RECEIPTS*\n";
        $message .= "━━━━━━━━━━━━━━━━━━\n\n";

        foreach ($transactions as $transaction) {

            $token = $transaction->stronPayments->token ?? 'Not Yet Generated';
            $formattedDate = $transaction->updated_at->format('d M Y, h:i A');

            $message .=                 
                "🆔 *Transaction ID:* {$transaction->id}\n" .
                "📄 *Reference:* {$transaction->transaction_ref}\n" .
                "🔥 *Meter Number:* {$transaction->meter_number}\n" .
                "📅 *Date:* {$formattedDate}\n" .
                "📌 *Status:* {$transaction->status}\n" .
                "🔑 *Token:* {$token}\n" .
                "━━━━━━━━━━━━━━━━━━\n\n";

        }

        $message .= "Type *MENU* to start again.";

        return trim($message);
        
    }
      

    public static function sendTokenButtons($phone)
    {
        return Http::withToken(config('services.whatsapp.token'))
            ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
            ->post(self::url(), [
                "messaging_product" => "whatsapp",
                "to" => $phone,
                "type" => "interactive",
                "interactive" => [
                    "type" => "button",
                    "body" => [
                        "text" => "What should I use to retrieve your Token?"
                    ],
                    "action" => [
                        "buttons" => [
                            [
                                "type" => "reply",
                                "reply" => [
                                    "id" => "METER_NUMBER",
                                    "title" => "Meter number"
                                ]
                            ],
                            [
                                "type" => "reply",
                                "reply" => [
                                    "id" => "TRANSACTION_REFERENCE",
                                    "title" => "Transaction Ref"
                                ]
                            ],
                            [
                                "type" => "reply",
                                "reply" => [
                                    "id" => "PHONE_NUMBER",
                                    "title" => "Phone Number"
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
        }


        public static function sendMenuButtons($phone)
        {
            return  Http::withToken(config('services.whatsapp.token'))
                ->withHeaders([
                            'Content-Type' => 'application/json',
                    ])
                ->post(self::url(), [
                    "messaging_product" => "whatsapp",
                    "to" => $phone,
                    "type" => "interactive",
                    "interactive" => [
                        "type" => "button",
                        "body" => [
                            "text" => "Welcome to *GASOL Uganda* Self Care Agent,\nWhat would you like to do?"
                        ],
                        "action" => [
                            "buttons" => [
                                [
                                    "type" => "reply",
                                    "reply" => [
                                        "id" => "BUY_GAS",
                                        "title" => "Buy Gas Token"
                                    ]
                                ],
                                [
                                    "type" => "reply",
                                    "reply" => [
                                        "id" => "RETRIEVE_TOKEN",
                                        "title" => "Retrieve Token"
                                    ]
                                ],
                                [
                                    "type" => "reply",
                                    "reply" => [
                                        "id" => "HELP",
                                        "title" => "Help"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]);
            }

}
 
