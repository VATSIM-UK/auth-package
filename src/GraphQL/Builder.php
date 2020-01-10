<?php


namespace VATSIMUK\Support\Auth\GraphQL;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use VATSIMUK\Support\Auth\Exceptions\APITokenInvalidException;

class Builder
{
    /* @var string */
    private $method;
    /* @var array */
    private $columns;
    /* @var string */
    private $action;
    /* @var string */
    private $arguments;

    /* @var string */
    private $executedQuery;

    /**
     * Builder constructor.
     * @param string $method
     * @param array $columns
     * @param string|null $arguments
     * @param string $action
     */
    public function __construct(string $method, array $columns, string $arguments = null, string $action = 'query')
    {
        $this->method = $method;
        $this->arguments = $arguments;
        $this->columns = $columns;
        $this->action = $action;
    }

    /**
     * Executes the current query
     *
     * @param string $token Optional Auth API token
     * @return Response
     * @throws BindingResolutionException
     * @throws APITokenInvalidException
     */
    public function execute(string $token = null): Response
    {
        if (! $token) {
            // Attempt to get Machine-Machine token
            $token = $this->getAuthAccessToken();
            if (! $token) {
                return Response::newServerErrorResponse($this, "Unable to retrieve Auth access token");
            }
        }

        // Create HTTP Client with Bearer
        $client = app()->make(Client::class);

        // Execute the query
        $this->executedQuery = $this->getGraphQLQuery();

        try {
            $response = json_decode(
                $client->post(config('ukauth.root_url') . config('ukauth.graphql_path'),
                    [
                        'form_params' => [
                            'query' => $this->getGraphQLQuery()
                        ],
                        'headers' => [
                            'Authorization' => "Bearer $token"
                        ]
                    ])->getBody()->getContents()
            );
        } catch (RequestException $e) {
            // Exception is thrown in the event of a networking error (connection timeout, DNS errors, etc.).
            // Likely that the service is down, or not responding to requests
            return Response::newServerErrorResponse($this);
        }


        if (! $response || ! $response instanceOf \stdClass) {
            return Response::newServerErrorResponse($this, "Unable to parse API response", $response);
        }

        $response = new Response($response, $this);

        if ($response->hasUnauthenticatedError()) {
            throw new APITokenInvalidException();
        }

        return $response;
    }

    /**
     * Check the pulse of the Auth API
     *
     * @return bool
     */
    public static function checkAlive(): bool
    {
        $client = resolve(Client::class);

        try {
            $response = json_decode($client->get(config('ukauth.root_url') . '/api/pulse')->getBody()->getContents());

            if ($response && $response->alive) {
                return true;
            }

        } catch (RequestException $e) {
            //TODO: Log to Bugsnag
        }

        return false;
    }

    /**
     * Generates or fetches Auth service token
     *
     * @return string|null
     */
    private function getAuthAccessToken(): ?string
    {
        $token = Cache::get('AUTH_API_TOKEN');

        if (! $token) {
            $client = resolve(Client::class);

            try {
                $response = $client->post(config('ukauth.root_url') . config('ukauth.oauth_path') . '/token', [
                    'form_params' => [
                        'grant_type' => 'client_credentials',
                        'client_id' => config('ukauth.machine_client_id'),
                        'client_secret' => config('ukauth.machine_client_secret'),
                        'scope' => '*'
                    ]
                ]);
                $token = json_decode((string)$response->getBody(), true)['access_token'];
                Cache::put('AUTH_API_TOKEN', $token, \DateInterval::createFromDateString("1 day"));
            } catch (RequestException $e) {
                // TODO: Log Exception. Likely either connection issue or output issue
                return null;
            }
        }
        return $token;
    }

    /**
     * Generate the complete GraphQL query
     *
     * @return string
     */
    public function getGraphQLQuery(): string
    {
        $query = $this->action . " {\n";
        $query .= $this->method . ($this->arguments ? " ($this->arguments){\n" : " {\n");
        $query .= $this->getColumns();
        $query .= "}\n}";
        return $query;
    }

    /*
     * Generates the body of the GraphQL request
     *
     * @return string
     */
    public function getColumns(): string
    {
        return $this->buildColumns($this->columns);
    }

    /**
     * Iterates through array of columns supplied, and converts into GraphQL query format
     *
     * @param array $rawColumns
     * @return string
     */
    private function buildColumns(array $rawColumns): string
    {
        $columnString = '';

        foreach ($rawColumns as $key => $column) {
            if(is_string($column)){
                if (Str::contains($column, ".")) {
                    data_fill($rawColumns, $column, Arr::last(explode('.', $column)));
                    unset($rawColumns[$key]);
                }else{
                    // Look for duplicates strings in the 1st Dimension of the columns array. Other duplicates caught later.
                    if(count(array_keys(array_filter($rawColumns, function($value){
                            return !is_array($value);
                        }), $column)) > 1){
                        unset($rawColumns[$key]);
                    }
                }
            }
        }

        foreach ($rawColumns as $key => $column) {
            if (is_array($column)) {
                $columnString .= "$key {\n";
                $columnString .= $this->buildColumns(array_unique($column));
                $columnString .= "}\n";
            } else {
                $columnString .= "$column\n";
            }
        }
        return $columnString;
    }

    /**
     * Gets the GraphQL method
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }
}
