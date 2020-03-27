<?php

namespace VATSIMUK\Support\Auth\Services;

use Illuminate\Support\Facades\Cookie;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use VATSIMUK\Support\Auth\Models\RemoteUser;

class JWTService
{
    /**
     * Creates a local auth JWT for the given user.
     *
     * @param RemoteUser $user
     * @param $expires_at
     * @param $accessToken
     * @return Token
     */
    public static function createToken(RemoteUser $user, $expires_at, $accessToken): Token
    {
        return (new Builder())
            ->issuedBy(url('/'))
            ->permittedFor(url('/'))
            ->issuedAt($time = time())
            ->expiresAt($expires_at)
            ->withClaim('id', $user->id)
            ->withClaim('name_first', $user->name_first)
            ->withClaim('name_last', $user->name_last)
            ->withClaim('roles', collect($user->roles)->pluck('name')->all())
            ->withClaim('all_permissions', $user->all_permissions)
            ->withClaim('access_token', $accessToken)
            ->withClaim('has_password', $user->has_password)
            ->getToken(new Sha256(), new Key(config('app.secret')));
    }

    /**
     * Validates a local auth JWT, and returns the user.
     *
     * @param string $token
     * @return bool|RemoteUser
     */
    public static function validateTokenAndGetUser(string $token)
    {
        $token = (new Parser())->parse($token);

        if (! $token->verify(new Sha256(), new Key(config('app.secret')))) {
            return false;
        }

        $issuedAt = $token->getClaim('iat');

        $userBaseInfo = [
            'id' => $token->getClaim('id'),
            'name_first' => $token->getClaim('name_first'),
            'name_last' => $token->getClaim('name_last'),
            'access_token' => $token->getClaim('access_token'),
            'has_password' => $token->getClaim('has_password'),
            'roles' => $token->getClaim('roles'),
            'all_permissions' => $token->getClaim('all_permissions'),
        ];

        // If has session id, check is same session. If not, force authentication
        if ($userBaseInfo['has_password'] && (! Cookie::get('ukauth_sesh_id') || decrypt(Cookie::get('ukauth_sesh_id')) != $issuedAt.$userBaseInfo['id'])) {
            return false;
        }

        return resolve(config('ukauth.auth_user_model'))::initModelWithData($userBaseInfo);
    }
}
