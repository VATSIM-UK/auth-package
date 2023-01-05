<?php

namespace VATSIMUK\Support\Auth\GraphQL;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use stdClass;
use VATSIMUK\Support\Auth\Models\RemoteModel;

class Response
{
    private $query;
    private $jsonResponse;

    public function __construct(stdClass $jsonResponse, Builder $query)
    {
        $this->jsonResponse = $jsonResponse;
        $this->query = $query;
    }

    /**
     * Returns whether the response contains errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return isset($this->jsonResponse->errors);
    }

    /**
     * Gets the errors from the query.
     *
     * @return array|null
     */
    public function getErrors(): ?array
    {
        if (! $this->hasErrors()) {
            return null;
        }

        return $this->jsonResponse->errors;
    }

    /**
     * @return bool
     */
    public function hasUnauthenticatedError(): bool
    {
        if (! $this->hasErrors()) {
            return false;
        }

        foreach ($this->getErrors() as $error) {
            $error = (object) $error;
            if (isset($error->debugMessage) && $error->debugMessage == 'Unauthenticated.') {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the body of the data response is empty
     * (Doesn't have errors, but method returned null (or empty array)).
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        if ($this->hasErrors()) {
            return false;
        }

        if (empty($this->jsonResponse->data->{$this->query->getMethod()})) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if the data object has been returned with no errors, and has parsed ok.
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return ! $this->hasErrors() && isset($this->jsonResponse->data);
    }

    /**
     * Gets the results from the query.
     *
     * @return stdClass|null
     */
    public function getResults()
    {
        if (! $this->isOk() || $this->isEmpty()) {
            return;
        }

        return $this->jsonResponse->data->{$this->query->getMethod()};
    }

    /**
     * Gets the results from the query hydrated as the given model.
     *
     * @param  RemoteModel|string  $modelClass
     * @return RemoteModel|Collection|null
     */
    public function getHydratedResults($modelClass)
    {
        if (! $this->getResults()) {
            return;
        }

        if (! is_object($modelClass)) {
            $modelClass = resolve($modelClass);
        }

        return ! Arr::isAssoc((array) $this->getResults()) ?
            $modelClass->newQueryWithoutScopes()->hydrate((array) $this->getResults())
            :
            $modelClass->newQueryWithoutScopes()->make((array) $this->getResults());
    }

    /**
     * Returns the response from the API.
     *
     * @return stdClass
     */
    public function getRawResponse(): stdClass
    {
        return $this->jsonResponse;
    }

    /**
     * Generates a error response class.
     *
     * @param $query
     * @param  string  $message
     * @param  null  $data
     * @return Response
     */
    public static function newServerErrorResponse($query, $message = 'There was a server error trying to run this query', $data = null)
    {
        Log::error("[AUTH API] Server Error Trying To Query ($message)", ['query' => $query, 'server_response' => $data]);
        //TODO: Log error to BugSnag

        return new self((object) [
            'errors' => [
                [
                    'message' => $message,
                ],
            ],
        ], $query);
    }
}
