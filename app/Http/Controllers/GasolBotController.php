<?php

namespace App\Http\Controllers;

use App\Models\GasolBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GasolBotController extends Controller
{
    public function handle(Request $request)
    {
     

        $message = $request->input('entry.0.changes.0.value.messages.0');


        // $res = array (
        //     'context' =>
        //         array (
        //             'from' => '15550324603',
        //             'id' => 'wamid.HBgMMjU2Nzg3NDQ0MDgxFQIAERgSQzAzM0NEOTE3RTY0MzNGOEM5AA==',
        //         ),
        //     'from' => '256787444081',
        //     'id' => 'wamid.HBgMMjU2Nzg3NDQ0MDgxFQIAEhgWM0VCMDJGRjU3MTNFREVBODZBNTY1RQA=',
        //     'timestamp' => '1772011650',
        //     'type' => 'interactive',
        //     'interactive' =>
        //         array (
        //             'type' => 'button_reply',
        //             'button_reply' =>
        //             array (
        //             'id' => 'HELP',
        //             'title' => 'Help',
        //             ),
        //         ),
        //     );


           Log::info($message);
        if (!$message) return response()->json();

        $phone = $message['from'];
        $text  = strtolower($message['text']['body'] ?? '');
        $button = $message['interactive']['button_reply']['title'] ?? null;

         Log::info($phone." -".$text."-".$button);

        // Reset flow
        if ($text === 'menu') {
            GasolBot::updateOrCreate(
                ['phone' => $phone],
                ['state' => 'START', 'meter_number' => null, 'amount' => null]
            );
        }

        $session = GasolBot::firstOrCreate(
            ['phone' => $phone],
            ['state' => 'START']
        );

        /* ================= START ================= */
        if ($session->state === 'START') {
            $this->sendMenuButtons($phone);
            $session->update(['state' => 'MENU']);
            return response()->json(['status' => 'received'], 200);
        }

        /* ================= MENU ================= */
        if ($session->state === 'MENU') {

            if ($button === 'Buy Gas Token') {
                $this->sendText($phone, "Please enter your meter number");
                $session->update(['state' => 'ENTER_METER']);
                return response()->json(['status' => 'received'], 200);
            }

            if ($button === 'Help') {
                $this->sendHelp($phone);
                $session->update(['state' => 'HELP']);
                return response()->json(['status' => 'received'], 200);
            }
        }

        /* ================= HELP ================= */
        if ($session->state === 'HELP') {
            if ($text === 'menu') {
                $this->sendMenuButtons($phone);
                $session->update(['state' => 'MENU']);
            }
            return response()->json(['status' => 'received'], 200);
        }

        /* ================= ENTER METER ================= */
        if ($session->state === 'ENTER_METER' && $text) {
            $session->update([
                'meter_number' => $text,
                'state' => 'ENTER_AMOUNT'
            ]);

            $this->sendText($phone, "Enter amount");
            return response()->json(['status' => 'received'], 200);
        }

        /* ================= ENTER AMOUNT ================= */
        if ($session->state === 'ENTER_AMOUNT' && is_numeric($text)) {

            //process payment

            $amount = intval($text);               

            $postData = [
                'mobile_number' => self::validatePhoneNumber($phone),
                'meter_number'  => $session->meter_number,
                'amount'        => $amount,
                'network'       => self::detectUgandaNetwork($phone),
            ];

            // $response = app(TransactionsController::class)->makePayment(new Request($postData));

            $response = collect();       

            $results = $response->getData(true);

            if(isset($results['status'])){

              $this->sendText(
                    $phone,
                    $results['msg']
                );

            }else{
                $this->sendText(
                    $phone,
                    "This payment transaction has not been successful"
                );
            }          

            $session->update(['state' => 'WAIT_PAYMENT']);

            return response()->json(['status' => 'received'], 200);
        }

        return response()->json(['status' => 'received'], 200);

    }



    private function sendText($phone, $message)
    {
        return Http::withToken(config('services.whatsapp.token'))
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->url(), [
                        "messaging_product" => "whatsapp",
                        "to" => $phone,
                        "type" => "text",
                        "text" => [
                            "body" => $message
                        ]
                    ]);
    }

    private function sendMenuButtons($phone)
    {
        Http::withToken(config('services.whatsapp.token'))
            ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
            ->post($this->url(), [
                "messaging_product" => "whatsapp",
                "to" => $phone,
                "type" => "interactive",
                "interactive" => [
                    "type" => "button",
                    "body" => [
                        "text" => "What would you like to do?"
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
                                    "id" => "HELP",
                                    "title" => "Help"
                                ]
                            ]
                        ]
                    ]
                ]
            ]);
        }

        private function sendHelp($phone)
        {
            $message = "*Gas Token Help*\n\n"
                ."• Buy Gas Token – Purchase gas using our app\n"
                ."• Enter correct meter number\n"
                ."• Tokens are credited instantly after payment\n\n"
                ."📞 Support: +256782033814\n\n"
                ."Type *MENU* to return";

            $this->sendText($phone, $message);
        }

        function url()
        {
            return "https://graph.facebook.com/"
                .config('services.whatsapp.version')
                ."/"
                .config('services.whatsapp.phone_id')
                ."/messages";
        }

        function webhook(Request $request) {

            Log::info($request);

            $token = $request->get('hub_verify_token');

            if($request->get('hub_mode')==="subscribe" && $token===config('services.whatsapp.myToken')){
                $hub_challenge=$request->get('hub_challenge');
                Log::info("HUB-CHALANGE-".$hub_challenge);
                return $hub_challenge;
           
             }else{

                return response()->json("Invalid code",403);
                
            }
 
        }

        public function validatePhoneNumber($phone)
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


        function detectUgandaNetwork($phone)
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


        // function getBotToken(){     

        //     $data = [
        //         'device_id'   => 'WHATSAPP_BOT_001',
        //         'device_name' => 'WhatsApp Payment Bot',
        //         'device_type' => 'web',
        //         'platform'    => 'web',
        //         'app_version' => '1.0.0',
        //     ];

        //     $request = new Request($data);

        //     $devive = app(DeviceAuthController::class);

        //     $response = $devive->register($request);

        //     $data = $response->getData(true);  

        //     return $data['data']['token'];
            
        // } 
}
