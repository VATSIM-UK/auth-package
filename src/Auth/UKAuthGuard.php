<?php


namespace VATSIMUK\Auth\Remote\Auth;


use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
use VATSIMUK\Auth\Remote\GraphQL\Builder;

class UKAuthGuard implements Guard
{

    protected $request;
    protected $provider;
    protected $user;

    /**
     * Create a new authentication guard.
     *
     * @param \Illuminate\Contracts\Auth\UserProvider $provider
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(UserProvider $provider, Request $request)
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->user = NULL;
        $this->validate();
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return !is_null($this->user()) && $this->validate();
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        if (!is_null($this->user)) {
            return $this->user;
        }
    }

    /**
     * Get the ID for the currently authenticated user.
     *
     * @return int|string|null
     */
    public function id()
    {
        if ($user = $this->user()) {
            return $this->user()->getAuthIdentifier();
        }
    }

    /**
     * Validate a user's credentials.
     *
     * @param array $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (!$this->request->bearerToken()) {
            if($this->user){
                return true;
            }
            return false;
        }

        $token = (new Parser())->parse((string)$this->request->bearerToken());

        if (!$token->verify(new Sha256(), new Key(config('app.secret')))) {
            return false;
        }

        $userBaseInfo = [
            'id' => $token->getClaim('uid'),
            'iat' => $token->getClaim('iat'),
            'name_first' => $token->getClaim('name_first'),
            'name_last' => $token->getClaim('name_last'),
            'auth_token' => $token->getClaim('access_token'),
            'session_locked' => $token->getClaim('session_locked'),
        ];

        // If has session id, check is same session. If not, force authentication
        if ($userBaseInfo['session_locked'] && (!Cookie::get('ukauth_sesh_id') || decrypt(Cookie::get('ukauth_sesh_id')) != $userBaseInfo['iat'].$userBaseInfo['id'])) {
            return false;
        }

        $this->setUser(config('ukauth.auth_user_model')::initModelWithData($userBaseInfo));

        // Attempt to check user's status with CAS
        if (Builder::checkAlive()) {
            $user = $this->user->fresh(['banned']);
            // Report unauthenticated if user doesn't exist (most likely token revoked) or is banned
            if (!$user || $user->banned) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the current user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }
}
