<?php


namespace VATSIMUK\Support\Auth\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Support\Collection;
use VATSIMUK\Support\Auth\Exceptions\APITokenInvalidException;
use VATSIMUK\Support\Auth\GraphQL\Builder;
use VATSIMUK\Support\Auth\Models\Concerns\HasPermissions;
use VATSIMUK\Support\Auth\Models\Concerns\HasRatings;

class RemoteUser extends RemoteModel
{
    use HasRatings, Authenticatable, HasPermissions, Authorizable;

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
     * @return Collection|RemoteModel|RemoteUser|null
     * @throws BindingResolutionException
     * @throws APITokenInvalidException
     */
    public static function findWithAccessToken(string $token, array $columns = null)
    {
        $query = new Builder('authUser', static::generateParams($columns));
        $response = $query->execute($token);

        return $response->getHydratedResults(self::class);
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
}
