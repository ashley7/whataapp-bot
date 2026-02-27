<?php

namespace App\Http\Controllers;

use App\Models\GasolBot;
use Illuminate\Http\Request;
 
use Illuminate\Support\Facades\Log;

class GasolBotController extends Controller
{
    public function handle(Request $request)
    {     

        $message = $request->input('entry.0.changes.0.value.messages.0');
 
           Log::info($message);

        if (!$message) return response()->json();

        $phone = $message['from'];
        $text  = strtolower($message['text']['body'] ?? '');
        $button = $message['interactive']['button_reply']['id'] ?? null;

         Log::info($phone." -".$text."-".$button);

       // Reset flow
        $key_words = GasolBot::keyWords();

        if (in_array($text,$key_words)) { 

            GasolBot::updateOrCreate( //reset session
                ['phone' => $phone], 
                [ 
                    'state' => 'START',
                    'meter_number' => null,
                    'amount' => null,
                ],
            ); 
        }    
        
        $session = GasolBot::firstOrCreate( 
            ['phone' => $phone],
            ['state' => 'START'] 
        );

        /* ================= START ================= */ 
        if ($session->state === 'START') { 
            GasolBot::sendMenuButtons($phone);
            $session->update(['state' => 'MENU']);
            return response()->json(['status' => 'received'], 200);
        }

        /* ================= MENU ================= */
        if ($session->state === 'MENU') {

            if ($button === 'BUY_GAS') {
                GasolBot::sendText($phone, "Please Enter Your *Meter Number*");
                $session->update(['state' => 'ENTER_METER']);
                return response()->json(['status' => 'received'], 200);
            }

            if ($button === 'HELP') {
                $this->sendHelp($phone);
                $session->update(['state' => 'HELP']);
                return response()->json(['status' => 'received'], 200);
            }

             if($button == 'RETRIEVE_TOKEN'){
                GasolBot::sendTokenButtons($phone);
                $session->update(['state' => 'RETRIEVE_TOKEN_OPTIONS']);
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

            $this->sendText($phone, "Enter *Amount* in Uganda Shillings.");
            return response()->json(['status' => 'received'], 200);
        }

        /* ================= ENTER PHONE NUMBER ================= */
        if ($session->state === 'ENTER_AMOUNT' && is_numeric($text)) {
            $session->update([
                'amount' => $text,
                'state' => 'ENTER_PHONE_NUMBER'
            ]);

            $this->sendText($phone, "Enter *Phone Number* to pay with.");
            return response()->json(['status' => 'received'], 200);

        } 

        /* ================= ENTER AMOUNT ================= */
        if ($session->state === 'ENTER_PHONE_NUMBER' && is_numeric($text)) {

            //process payment

            $phone_number = $text;
            
            $payment_phone_number = GasolBot::validatePhoneNumber($phone_number);

            $network = GasolBot::detectUgandaNetwork($payment_phone_number);

            $postData = [
                'mobile_number' => $payment_phone_number,
                'meter_number'  => $session->meter_number,
                'amount'        => $session->amount,
                'network'       => $network,
            ];

            // $response = app(TransactionsController::class)->makePayment(new Request($postData));

            $message_response = "A payment of UGX ".number_format($session->amount)." has been initiated on\nYour *$network* Phone number *$payment_phone_number*, \nPlease approve it. \nType *MENU* to start over again.";

            $this->sendText(
                    $phone,
                    $message_response
                );             

            // $results = $response->getData(true);

            // if(isset($results['status'])){

            //   $this->sendText(
            //         $phone,
            //         $results['msg']
            //     );

            // }else{
            //     $this->sendText(
            //         $phone,
            //         $message_response
            //     );
            // }        
            
            $session->update(['state' => 'MENU']);

            // $session->update(['state' => 'WAIT_PAYMENT']);

            return response()->json(['status' => 'received'], 200);
        }


        if ($session->state === 'RETRIEVE_TOKEN_OPTIONS') {

            if ($button === 'METER_NUMBER') {
                GasolBot::sendText($phone, "Please Enter Your *Meter Number*.");
                $session->update(['state' => 'RETRIEVE_WITH_METER']);
                return response()->json(['status' => 'received'], 200);
            }

            if ($button === 'TRANSACTION_REFERENCE') {
                GasolBot::sendText($phone, "Please Enter The *Transaction Reference*.");
                $session->update(['state' => 'RETRIEVE_WITH_TREF']);
                return response()->json(['status' => 'received'], 200);
            }

            if ($button === 'PHONE_NUMBER') {
                GasolBot::sendText($phone, "Please Enter *Phone Number* That you used to buy the Gas Token");
                $session->update(['state' => 'RETRIEVE_WITH_PHONE_NUMBER']);
                return response()->json(['status' => 'received'], 200);
            }             

        } 

            if ($session->state === 'RETRIEVE_WITH_METER') {

            $message = GasolBot::getTransactions($session->state,$text);

            GasolBot::sendText($phone, $message);
            
            return response()->json(['status' => 'received'], 200);
 
        }

        if ($session->state === 'RETRIEVE_WITH_TREF') {

            $message = GasolBot::getTransactions($session->state,$text);

            GasolBot::sendText($phone, $message);
            
            return response()->json(['status' => 'received'], 200);

        }

        if ($session->state === 'RETRIEVE_WITH_PHONE_NUMBER') {

            $message = GasolBot::getTransactions($session->state,$text);

            GasolBot::sendText($phone, $message);
            
            return response()->json(['status' => 'received'], 200);

        }

        return response()->json(['status' => 'received'], 200);

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
 
}
