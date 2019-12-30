<?php

namespace VATSIMUK\Auth\Remote\Controllers;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use VATSIMUK\Auth\Remote\Auth\UKAuthJwtService;
use VATSIMUK\Auth\Remote\Models\RemoteUser;

class AuthenticationController extends Controller
{
    public function login(Request $request)
    {
        $query = http_build_query([
            'client_id' => config('ukauth.client_id'),
            'redirect_uri' => route('auth.login.verify'),
            'response_type' => 'code',
            'scope' => '',
        ]);

        return redirect(config('ukauth.root_url') . config('ukauth.oauth_path') . '/authorize?' . $query);
    }

    public function verifyLogin(Request $request)
    {
        $http = new Client();
        //TODO: Handle error 400 bad request. This happens when the code given has expired, been timed out, or already consumed
        $response = $http->post(config('ukauth.root_url') . config('ukauth.oauth_path') . '/token', [
            'form_params' => [
                'grant_type' => 'authorization_code',
                'client_id' => config('ukauth.client_id'),
                'client_secret' => config('ukauth.client_secret'),
                'redirect_uri' => route('auth.login.verify'),
                'code' => $request->code,
            ],
        ]);

        $response = json_decode((string)$response->getBody(), true);

        // Create JWT for service auth
        $user = RemoteUser::findWithAccessToken($response['access_token'], ['name_first', 'name_last', 'has_password', 'roles' => [
            'name'
        ], 'all_permissions']);
        $expires = Carbon::now()->addSeconds($response['expires_in'])->getTimestamp();

        $token = UKAuthJwtService::createToken($user, $expires, $response['access_token']);


        return redirect(url('/auth/complete') . "?token=$token&expires_at=$expires")
            ->withCookies([cookie('ukauth_sesh_id', encrypt($token->getClaim('iat').$user->id))]);
    }
}
