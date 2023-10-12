<?php

namespace App\Http\Controllers;

use Twilio\Rest\Client;
use App\Models\Response;
use Illuminate\Http\Request;
use GuzzleHttp\Exception\RequestException;

class ChatBotController extends Controller
{
    public function listenToReplies(Request $request)
    {  
        $from = $request->input('From');//client phone number
        $body = $request->input('Body');//client whatsapp message
       //UTILITY VARABLES 
       $match_counts = 0; //count keyword matches
       $weight = 0; //weight of each response
       $response_id=0;
       $response_message="Sorry, we could not find a response";
        $client = new \GuzzleHttp\Client(); //making request to web servers 
        //create an array from $body 
        $client_keywords = explode(" ",$body);

        try{ 
            //fetch all responses 
            $responses = Response::all(); 
            //loop through all responses
            foreach($responses as $response){
                  //break the keywords into an array 
                  $bot_keywords = explode(" ",$response->keywords);
                  //loop through every client keyword and match with bot keywords 
                  foreach($client_keywords as $client_keyword){
                     //loop through the bot keyword 
                     foreach($bot_keywords as $bot_keyword){
                        //perform keyword matching  
                        if( strtolower($bot_keyword)  == strtolower($client_keyword)){
                           //increment the $match_counts  
                            $match_counts++;
                        }//endof keyword matching
                     }//endof looping bot_keywords 
                     //perform keyword weight calculation 
                     $this_response_weight = ($match_counts/count($client_keywords)) * 100;
                     //update the most likely response 
                     if($this_response_weight > $weight){
                        $weight = $this_response_weight;
                        $response_id = $response['id'];
                        $response_message = $response['reply'];
                     }//endof response update

                  }//endof looping client keywords
            $match_counts = 0;
            }//endof looping responses
            //send to user 
            $this->sendWhatsAppMessage($response_message, $from);

        }catch(RequestException $th){
            $response = json_decode($th->getResponse()->getBody());
            $this->sendWhatsAppMessage($response->message, $from);

        }
    
        return;
    }//endof listen to replies

    /**
     * Sends a WhatsApp message  to user using
     * @param string $message Body of sms
     * @param string $recipient Number of recipient
     */
    public function sendWhatsAppMessage(string $message, string $recipient)
    {
        $twilio_whatsapp_number = getenv('TWILIO_WHATSAPP_NUMBER');
        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");

        $client = new Client($account_sid, $auth_token);
        return $client->messages->create($recipient, array('from' => "whatsapp:$twilio_whatsapp_number", 'body' => $message));
    }
}
