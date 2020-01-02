<?php


namespace VATSIMUK\Support\Auth\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;
use VATSIMUK\Support\Auth\Exceptions\APITokenInvalidException;
use VATSIMUK\Support\Auth\GraphQL\Builder;
use VATSIMUK\Support\Auth\Models\Concerns\HasRatings;

class RemoteUser extends RemoteModel implements Authenticatable
{
    use HasRatings;

    protected static $singleMethod = "user";
    protected static $manyMethod = "users";

    protected static $defaultFields = [
        "name_first",
        "name_last",
    ];

    /**
     * Finds the user by their Auth API Access Token
     *
     * @param string $token
     * @param array $columns
     * @return RemoteUser
     * @throws BindingResolutionException
     * @throws APITokenInvalidException
     */
    public static function findWithAccessToken(string $token, array $columns = null): ?self
    {
        $query = new Builder('authUser', static::generateParams($columns));
        $response = $query->execute($token);

        return ! $response->isEmpty() ? static::initModelWithData($response->getResults()) : null;
    }

    /**
     * @param array|null $columns
     * @param string|null $token
     * @return static|null
     * @throws BindingResolutionException
     * @throws APITokenInvalidException
     */
    public function fresh($columns = [], string $token = null)
    {
        if ($this->access_token) {
            return static::findWithAccessToken($this->access_token, $columns);
        }

        return parent::fresh($columns, $token);
    }

    /**
     * Gets the user's full name
     *
     * @return string|null
     */
    public function getNameAttribute(): ?string
    {
        if (! $this->name_first && ! $this->name_last) {
            return $this->id;
        }
        return "{$this->name_first} {$this->name_last}";
    }

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName(): string
    {
        return "id";
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword(): string
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken(): string
    {
        return null;
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        return;
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(): string
    {
        return "remember_token";
    }
}
