<?php

namespace VATSIMUK\Support\Auth\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use VATSIMUK\Support\Auth\Models\RemoteUser;
use VATSIMUK\Support\Auth\Services\JWTService;

class AuthenticationController extends Controller
{
    public function login()
    {
        $query = http_build_query([
            'client_id' => config('ukauth.client_id'),
            'redirect_uri' => route('auth.login.verify'),
            'response_type' => 'code',
            'scope' => '',
        ]);

        return redirect(config('ukauth.root_url').config('ukauth.oauth_path').'/authorize?'.$query);
    }

    public function verifyLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect(route('auth.login'));
        }

        $response = Http::post(config('ukauth.root_url').config('ukauth.oauth_path').'/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('ukauth.client_id'),
            'client_secret' => config('ukauth.client_secret'),
            'redirect_uri' => route('auth.login.verify'),
            'code' => $request->code,
        ]);

        if ($response->clientError()) {
            if ($response->status() == 400 && Str::contains($response->body(), 'invalid_request')) {
                Log::info("User at {$request->ip()} tried to verify their Auth SSO login, however the details were invalid", ['exception' => $e]);

                return redirect(route('auth.login'));
            }
            $response->throw();
        }

        $response = json_decode((string) $response->getBody(), true);

        // Create JWT for service auth
        $user = RemoteUser::findWithAccessToken($response['access_token'], ['name_first', 'name_last', 'has_password', 'roles' => [
            'name',
        ], 'all_permissions']);
        $expires = Carbon::now()->addSeconds($response['expires_in'])->getTimestamp();

        $token = JWTService::createToken($user, $expires, $response['access_token']);

        return redirect(url('/auth/complete')."?token=$token&expires_at=$expires")
            ->withCookies([cookie('ukauth_sesh_id', encrypt($token->getClaim('iat').$user->id))]);
    }
}
