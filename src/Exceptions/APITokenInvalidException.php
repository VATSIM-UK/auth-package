<?php


namespace VATSIMUK\Support\Auth\Exceptions;


class APITokenInvalidException extends \Exception
{
    protected $message = "The API token used for the query was invalid";
}