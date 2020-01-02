<?php


namespace VATSIMUK\Support\Auth\GraphQL;


use Illuminate\Support\Facades\Log;

class Response
{
    private $query;
    private $jsonResponse;

    public function __construct(\stdClass $jsonResponse, Builder $query)
    {
        $this->jsonResponse = $jsonResponse;
        $this->query = $query;
    }

    /**
     * Returns whether the response contains errors
     * @return bool
     */
    public function hasErrors(): bool
    {
        return isset($this->jsonResponse->errors);
    }

    /**
     * Returns whether the body of the data response is empty
     * @return bool
     */
    public function isEmpty(): bool
    {
        if (!$this->hasErrors() && $this->jsonResponse->data->{$this->query->getMethod()}) {
            return false;
        }
        return true;
    }

    /**
     * @return bool
     */
    public function isOk(): bool
    {
        return !$this->hasErrors();
    }

    /**
     * @return bool
     */
    public function hasUnauthenticatedError(): bool
    {
        if($this->isOk()){
            return false;
        }

        foreach ($this->getErrors() as $error){
            if ($error->debugMessage == "Unauthenticated."){
                return true;
            }
        }
        return false;
    }

    /*
     * Getters
     */

    /**
     * Gets the results from the query
     * @return \stdClass|null
     */
    public function getResults()
    {
        if ($this->isEmpty()) {
            return null;
        }

        return $this->jsonResponse->data->{$this->query->getMethod()};
    }


    /**
     * Gets the errors from the query
     * @return array|null
     */
    public function getErrors()
    {
        if (!$this->hasErrors()) {
            return null;
        }
        return $this->jsonResponse->errors;
    }

    /**
     * Generates a error response class
     * @param $query
     * @param string $message
     * @param null $data
     * @return Response
     */
    public static function newServerErrorResponse($query, $message = "There was a server error trying to run this query", $data = null)
    {
        Log::error("[AUTH API] Server Error Trying To Query ($message)", ['query' => $query, 'server_response' => $data]);
        //TODO: Log error to bugsnag

        return new self((object) [
            'errors' => [
                [
                    'message' => $message
                ]
            ]
        ], $query);
    }
}
