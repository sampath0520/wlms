<?php

namespace App\Http\Controllers;

use App\Constants\AppConstants;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use League\OAuth2\Client\Provider\GenericProvider;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

class ZoomController extends Controller
{

    // protected $userService;
    // protected $PasswordRestTokenService;
    // public function __construct(UserService $userService, PasswordRestTokenService $PasswordRestTokenService)
    // {
    //     $this->userService = $userService;
    //     $this->PasswordRestTokenService = $PasswordRestTokenService;
    //     $this->middleware('auth:api', ['except' => ['login', 'adminLogin']]);
    // }

    public function createWebinar()
    {
        $clientId = AppConstants::ZOOM_CLIENT_ID;
        $clientSecret = AppConstants::ZOOM_CLIENT_SECRET;
        $redirectUri = AppConstants::ZOOM_REDIRECT_URI;

        // Redirect the user to the Zoom authorization page
        $authorizationUrl = "https://zoom.us/oauth/authorize?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}";
        dd($authorizationUrl);
        return redirect($authorizationUrl);
    }

    public function handleZoomCallback(Request $request)
    {

        $clientId = AppConstants::ZOOM_CLIENT_ID;
        $clientSecret = AppConstants::ZOOM_CLIENT_SECRET;
        $redirectUri = AppConstants::ZOOM_REDIRECT_URI;

        // $code = $request->query('code');

        $tokenResponse = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode($clientId . ':' . $clientSecret),
        ])->post('https://zoom.us/oauth/token', [
            'grant_type' => 'authorization_code',
            'code' => $request->code,
            'redirect_uri' => 'https://689c-123-231-15-68.ngrok-free.app/api/webinar/callback',
        ]);

        if ($tokenResponse->successful()) {
            $accessToken = $tokenResponse->json('access_token');

            $webinarResponse = Http::withToken($accessToken)->post('https://api.zoom.us/v2/users/me/webinars', [
                'topic' => 'My Webinar',
                'type' => 5,
                'start_time' => now()->addDays(7)->setTimezone('UTC')->toISOString(),
                'duration' => 120,
            ]);
            $responseBody = $webinarResponse->json();

            // $client = new Client();
            // $response = $client->request('POST', 'https://api.zoom.us/v2/users/me/meetings', [
            //     'headers' => [
            //         'Authorization' => 'Bearer ' . $accessToken,
            //         'Content-Type' => 'application/json',
            //         'Accept' => 'application/json',
            //     ],
            //     'json' => [
            //         'topic' => 'My Meeting',
            //         'type' => 2,
            //         'start_time' => now()->addDays(7)->setTimezone('UTC')->toISOString(),
            //         'duration' => 120,
            //     ],
            // ]);

            // $responseBody = json_decode($response->getBody()->getContents());

            if ($responseBody->successful()) {
                $webinarData = $webinarResponse->json();

                // Redirect to a success page or display webinar data
                return response()->json($webinarData);
            } else {
                dd($responseBody);
                // Handle error response
                return response()->json(['error' => 'Failed to create Zoom webinar'], 500);
            }
        } else {
            // Handle token error response
            return response()->json(['error' => 'Failed to obtain access token'], 500);
        }
    }
}
