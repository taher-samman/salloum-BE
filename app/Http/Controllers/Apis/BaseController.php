<?php

namespace App\Http\Controllers\Apis;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Support\Facades\Http;

class BaseController extends Controller
{
    /**
     * success response method.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendResponse($result, $message = '')
    {
        $response = [
            'success' => true,
            'data'    => $result,
            'message' => $message,
        ];

        \Log::info('[' . date('m/d/Y h:i:s') . ' BaseController sendResponse $response]:' . json_encode($response));
        return response()->json($response, 200);
    }
    /**
     * return error response.
     *
     * @return \Illuminate\Http\Response
     */
    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }
        \Log::info('[BaseController sendError $response]:' . json_encode($response));
        return response()->json($response, $code);
    }

    static function token()
    {
        $client_id = \Config('services.google.clientId');
        $client_secret = \Config('services.google.clientSecret');
        $refresh_token = \Config('services.google.refreshToken');

        $response = Http::post('https://oauth2.googleapis.com/token', [

            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
            'grant_type' => 'refresh_token',

        ]);
        \Log::info($response->getBody());
        $accessToken = json_decode((string) $response->getBody(), true)['access_token'];

        return $accessToken;
    }
}
