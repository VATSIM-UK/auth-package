<?php

namespace VATSIMUK\Support\Auth\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use VATSIMUK\Support\Auth\GraphQL\Builder;
use VATSIMUK\Support\Auth\Services\JWTService;

class UKAuthGuard implements Guard
{
    protected $request;
    protected $provider;
    protected $user;

    /**
     * Create a new authentication guard.
     *
     * @param  UserProvider  $provider
     * @param  Request  $request
     * @return void
     */
    public function __construct(UserProvider $provider, Request $request)
    {
        $this->request = $request;
        $this->provider = $provider;
        $this->user = null;
        $this->validate();
    }

    /**
     * Determine if the current user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user()) && $this->validate();
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();
    }

    /**
     * Get the currently authenticated user.
     *
     * @return Authenticatable|null
     */
    public function user()
    {
        if (! is_null($this->user)) {
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
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        if (! $this->request->bearerToken()) {
            if ($this->user) {
                return true;
            }

            return false;
        }

        $user = JWTService::validateTokenAndGetUser($this->request->bearerToken());
        if (! $user) {
            return false;
        }

        $this->setUser($user);

        // Attempt to check user's status with CAS
        if (Builder::checkAlive()) {
            $user = $this->user->fresh(['banned']);
            // Report unauthenticated if user doesn't exist (most likely token revoked) or is banned
            if (! $user || $user->banned) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set the current user.
     *
     * @param  Authenticatable  $user
     * @return void
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }
}
