# VATSIM UK CAS Laravel Package

[![Build Status](https://travis-ci.com/VATSIM-UK/auth-package.svg?branch=master)](https://travis-ci.com/VATSIM-UK/auth-package)
## About
This package provides the boilerplate implementation to quickly and easily interface with the VATSIM UK Central Authentication Service (CAS). It allows for Eloquent-esque relationships and niceties to be maintained, whilst still keeping each service separate from each other.

## Installation

We recommend installation via Composer:
`composer require vatsimuk/auth-package`

### API & Authentication

You must define the following environment variables in the `.env` file to ensure the API can be reached:
* `AUTH_ROOT` - The root of the UK Auth application
* `AUTH_CLIENT_ID` & `AUTH_CLIENT_SECRET` - The Applications Client ID and Secret from the Auth Application (i.e. to access authenticated user's data). Make this client with `php artisan passport:client --name="Example Microservice"` in the Auth service. Callback should route to the service this package is being installed to. Replace myapp.test with the appropriate path - `http://myapp.test/auth/login/verify`
* `AUTH_MACHINE_CLIENT_ID` & `AUTH_MACHINE_CLIENT_SECRET` - The Applications Client ID and Secret from the Auth Application, to be used for Machine-Machine authentication (i.e. large scale user data collection). **Can't** be the same as the above set of credentials. Make this client with `php artisan passport:client --client --name="Machine-Machine ClientCred Grant"` in the Auth service.

### Authentication Setup
Assuming you want to leverage the SSO OAuth implementation via the UK Auth app:

1. In `config/auth.php`, set the default guard to `ukauth`
    ```php
    'defaults' => [
        'guard' => 'ukauth',
        'passwords' => 'users',
    ],
    ```
 
2. In `config/auth.php`, add in the `ukauth` guard as shown below:
    ```php
    'guards' => [
        
        ... Default Guards Here ...
    
        'ukauth' => [
            'driver' => 'jwt',
            'provider' => 'users',
        ],
    ],
    ```


## Usage

### Remote Relationships
The auth package has the powerful ability to contact the VATSIM UK CAS for core user data using the built-in eloquent query signatures.

To setup a new remote relationship, simply use the standard Laravel relationship methods with the default `RemoteUser` class (or your own if you have extended it):
```php
public function user() {
    return $this->belongsTo(RemoteUser::class);
}
```

> **Important**: You must use the `HasRemoteRelationships` trait included with the package in the model with the relationship to ensure that mocking, API offline handling and other functions operate as intended.


   
It's as simple as that! You can use the standard `auth` route middleware to protect routes.

Note that by default, the `Auth` facade will return instances of `VATSIMUK\Support\Auth\Models\RemoteUser`. If you want to leverage your own extension of the RemoteUser model with relationships and such, create a `ukauth.php` config file like so:
```php
<?php

return [
  'auth_user_model' => \App\User::class
];
```

### Authentication Workflow

Currently:
1. The front-end should redirect users without an authorization token to `http://myapp.test/auth/login` to authenticate. The front-end should remember the intended url for after login is complete.
2. After login, the user will be redirected to `http://myapp.test/auth/login/complete` with an authorisation token in the `token` query string. This token should be passed as via Bearer in the HTTP Authorization header. You should setup a route on this path to handle the post-login event.

After login via the SSO as above, the user is returned to `/auth/login/complete`, along with the JWT `token` in the query string. This should be passed as a bearer token with each request to the API to authorise the user's request. Middleware and Authentication guards are built into the package, so that you can use `Auth::user()` and `Auth::check()` as normal, as well as the `auth` middleware.

### JWT Contents
After the user has signed in via SSO they will be redirected as discussed above. The JWT is application specific, in that the JWT provided will only work and be accepted on the application is was generated on. It is also encrypted to prevent tampering with the JWT. The user's JWT contains several useful properties for the user, to prevent having to constantly call the Auth API for user details. These details are listed below - note that they will be automatically filled in to the user when accessed via `Auth::user()`.

|Property| Description |
|--|--|
| id | The user's VATSIM CID |
| name_first | The user's first name |
| name_last | The user's last name |
| access_token| The user's Auth API access token |
| has_password (bool)| Whether or not the user has a secondary password set |
| roles (array)| A list of the roles the user belongs to, by name. (e.g ["Role 1", "Role 2", ...]) |
| permissions (array)| A list of all the user's permissions (e.g ["auth.permissions.create", "ukts.members.manage", ...]) |

### Important Notes
* In order for the requirement for users with secondary authentication (a password) to have to re-authenticate with their password each session to be met, cookies should be sent with calls to the API
