<?php

namespace VATSIMUK\Support\Auth\Tests\Fixtures;

class MockJsonResponse
{
    /**
     * Represents a successfully retrieved user.
     *
     * @return mixed
     */
    public static function successfulAuthUserResponse()
    {
        return json_decode('{"data":{"authUser":{"id":"1300005","name_first":"5th","name_last":"Test","email":"joe.bloggs@example.org"}}}', true);
    }

    /**
     * Represents a successfully retrieved user.
     *
     * @param string $method
     * @return mixed
     */
    public static function successfulResponse($method = "user")
    {
        return json_decode('{"data":{"'.$method.'":{"id":"1300005","name_first":"5th","name_last":"Test","email":"joe.bloggs@example.org"}}}', true);
    }

    /**
     * Represents successfully retrieved users.
     *
     * @return mixed
     */
    public static function successfulMultipleResponse($method = "users")
    {
        return json_decode('{"data":{"'.$method.'":[{"id":"1300001","name_first":"1st","name_last":"Test"}, {"id":"1300005","name_first":"5th","name_last":"Test"}]}}', true);
    }

    /**
     * Mock response for when a user is not found.
     *
     * @return mixed
     */
    public static function emptyResponse()
    {
        return json_decode('{"data":{"user":null}}', true);
    }

    public static function erroredResponse()
    {
        return json_decode('{"errors": [ { "message": "There was an error" } ]}', true);
    }



    public static function unauthenticatedResponse()
    {
        return [
            "errors" => [
                ["debugMessage" => "Unauthenticated."]
            ]
        ];
    }
}
