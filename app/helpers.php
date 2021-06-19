<?php

use Illuminate\Support\Facades\Http;

function getUser($userId) 
{
    //get users data from users service by id using URL users services
    $url = env('SERVICE_USER_URL').'users/'.$userId;

    try {
        $response = Http::timeout(10)->get($url);

        $data = $response->json();
        
        //inject http code to $data
        $data['http_code'] = $response->getStatusCode();

        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'Services user unavailable'
        ];
    }
    
}

function getUserByIds($userIds = [])
{
    $url = env('SERVICE_USER_URL').'users/';

    try {
        if(count($userIds) === 0){
            return [
                'status' => 'success',
                'http_code' => 200,
                'data' => []
            ];
        }

        $response = Http::timeout(10)->get($url, ['user_ids[]' => $userIds]);

        $data = $response->json();

        $data['http_code'] = $response->getStatusCode();

        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'Services user unavailable'
        ];
    }
}

function postOrder($params){
    $url = env('SERVICE_ORDER_PAYMENT_URL').'api/orders';

    try {
        $response = Http::post($url, $params);
        $data = $response->json();

        $data['http_code'] = $response->getStatusCode();
        return $data;
    } catch (\Throwable $th) {
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => 'Services order-payment unavailable'
        ];
    }

}