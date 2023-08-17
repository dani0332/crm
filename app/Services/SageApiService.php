<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;


class SageApiService
{
    protected $sageLogin;
    protected $sagePassword;
    protected $sageRequestUrl;

       public function __construct()
       {
        //Guzzle was not working for post request 
        $this->sageLogin        = env('SAGE_300_LOGIN');
        $this->sagePassword     = env('SAGE_300_PASSWORD');
        $this->sageRequestUrl   = env('SAGE_300_BASE_URL').env('SAGE_300_VERSION'); 
       }


       public function postToSage300($endPoint,$payLoad)
       {
           // Create the payload data for the POST request           
            $sageEndPoint = $this->sageRequestUrl.$endPoint;
        
            $ch = curl_init($sageEndPoint);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payLoad));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
            ]);
            // Add basic authentication
            curl_setopt($ch, CURLOPT_USERPWD, "$this->sageLogin:$this->sagePassword");
            $response = curl_exec($ch); 
            if ($response === false || $response == '') {
                $errorResponse = curl_error($ch);
                $errorResponse = json_decode($errorResponse, true);
            // echo $errorResponse; exit;
                if (is_array($errorResponse)) {
                    $httpCode = $errorResponse['error']['code'];
                    
                    if (isset($errorResponse['error']['message']['value'])) {
                        $errorMessage = $errorResponse['error']['message']['value'];
                    } else {
                        $errorMessage = 'An error occurred';
                    }
                    $response = response()->json(['error' => $errorMessage, 'code' => $httpCode], $httpCode);                
                } else {                
                    $httpCode = 401;
                    $response = response()->json(['error' => 'Verify sage api credentials', 'code' => $httpCode], $httpCode);
                }

            }
            curl_close($ch);
            // Return response or handle errors
            return $response;
        }
}
