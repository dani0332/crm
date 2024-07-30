<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Enums\ApplicationStorageEnums;

class BirdService extends BaseService
{
    private $birdEndpoint = 'https://docs.bird.com';
    private $birdUserName;
    private $birdPassword;

    public function __construct(CustomerService $customerService)
    {
        $this->birdEndpoint = config('constants.BIRD_API_ENDPOINT') ?? 'https://docs.bird.com';
        $this->birdUserName = config('constants.BIRD_BASIC_AUTH_USER_NAME');
        $this->birdPassword = config('constants.BIRD_BASIC_AUTH_PASSWORD');
    }


    public function birdRequest($method ='get',$url, $data){

        $authBasic = base64_encode($this->birdUserName.':'.$this->birdPassword);
        try {
                  // Configure the HTTP request with headers
                $request = Http::withHeaders([
                    'Authorization' => 'Basic ' . $authBasic,
                    'Content-Type' => 'application/json', // Adjust if needed
                ]);

                // Dynamically call the HTTP method with the appropriate data
                if (in_array(strtolower($method), ['post', 'put', 'patch'])) {
                    $response = $request->$method($url, $data);
                } else {
                    $response = $request->$method($this->birdEndpoint.$url, ['query' => $data]);
                }
              // Return status code and response body
            return [
                'status_code' => $response->status(),
                'body' => $response->body() // or $response->body() for raw response
            ];
        } catch (\Exception $e) {
            // Log the error with details
            Log::error('Bird API request failed', [
                'method' => $method,
                'url' => $url,
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            // return response()->json(['error' => 'API request failed'], 500);
            throw $e;
        }
    }


    public function createContactIdentifier($data){

        $this->birdRequest('post', "/workspaces/123/contacts/$data['uuid']/identifiers", $data);
    }



}
