<?php

/**
 * App.net API Kit for PHP5. Requires curl.
 *
 * Author: David Wilkinson
 * Web: http://dopiaza.org/
 *
 * Copyright (c) 2012 David Wilkinson
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of
 * the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS
 * OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

class DPZFAppNet
{
    const VERSION = 0.1;

    /**
     * Session variable name used to store authentication data
     */
    const SESSION_OAUTH_DATA = 'DPZAppNetSessionOauthData';

    /**
     * Key names for various authentication data items
     */
    const OAUTH_CODE = 'oauth_code';
    const OAUTH_ACCESS_TOKEN = 'oauth_access_token';
    const USER_NAME = 'username';
    const USER_ID = 'user_id';
    const SCOPE = 'scope';
    const IS_AUTHENTICATING = 'is_authenticating';

    /**
     * Default timeout in seconds for HTTP requests
     */
    const DEFAULT_HTTP_TIMEOUT = 30;

    /**
     * Various API endpoints
     */
    const AUTH_ENDPOINT = 'https://alpha.app.net/oauth/authenticate';
    const ACCESS_TOKEN_ENDPOINT = 'https://alpha.app.net/oauth/access_token';
    const API_ENDPOINT = 'https://alpha-api.app.net';

    /**
     * @var string app.net client id
     */
    private $clientId;

    /**
     * @var string app.net client secret
     */
    private $clientSecret;

    /**
     * @var string Redirect URI for authentication
     */
    private $callback;

    /**
     * @var int HTTP Response code for last call made
     */
    private $lastHttpResponseCode;

    /**
     * @var int Timeout in seconds for HTTP calls
     */
    private $httpTimeout;

    /**
     * Create a new DPZAppNet object
     *
     * @param string $clientId The client id
     * @param string $clientSecret The client secret
     * @param string $callback The redirect URI for authentication
     */
    public function __construct($clientId, $clientSecret, $callback = NULL)
    {
        session_start();

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->callback = $callback;

        $this->httpTimeout = self::DEFAULT_HTTP_TIMEOUT;
    }

    /**
     * Call the app.net API
     *
     * @param string $path The path for the URI
     * @param array $parameters Any optional parameters
     * @return mixed|null The response object
     */
    public function call($path, $parameters = NULL, $method = 'GET')
    {
        $requestParams = ($parameters == NULL ? array() : $parameters);

        $response = $this->httpRequest(self::API_ENDPOINT . $path, $requestParams, $method);
        $data = json_decode($response);

        return empty($response) ? NULL : json_decode($response);
    }

    /**
     * Initiate the authentication process. Note that this method might not return - the user may get redirected to
     * App.net to approve the request.
     *
     * @param string $scope - the scope
     */
    public function authenticate($scope = 'stream')
    {
        $ok = false;

        // First of all, check to see if we're part way through the authentication process
        if ($this->getOauthData(self::IS_AUTHENTICATING))
        {
            $oauthCode = @$_GET['code'];

            if (!empty($oauthCode))
            {
                // Looks like we're in the callback
                $this->setOauthData(self::OAUTH_CODE, $oauthCode);
                $ok = $this->obtainAccessToken($oauthCode);
            }

            $this->setOauthData(self::IS_AUTHENTICATING, false);
        }

        $ok = ($this->isAuthenticated() && $this->doWeHaveTheRightScope($scope));

        if (!$ok)
        {
            // We're authenticating afresh, clear out the session just in case there are remnants of a
            // previous authentication in there
            $this->signout();

            // Redirect to app.net for authentication/authorisation
            // Make a note in the session of where we are first
            $this->setOauthData(self::IS_AUTHENTICATING, true);
            $this->setOauthData(self::SCOPE, $scope);

            $params = array(
                'client_id' => $this->clientId,
                'response_type' => 'code',
                'redirect_uri' => $this->callback,
                'scope' => $scope,
            );

            $queryString = $this->joinParameters($params);

            header(sprintf('Location: %s?%s',
                self::AUTH_ENDPOINT,
                $this->joinParameters($params)
            ));
            exit(0);
        }

        return $ok;
    }

    /**
     * Sign the current user out of the current DPZAppNet session. Note this doesn't affect the user's state on the
     * app.net web site itself, it merely removes the current access token from the session.
     *
     */
    public function signout()
    {
        unset($_SESSION[self::SESSION_OAUTH_DATA]);
    }

    /**
     * Is the current session authenticated on App.net
     *
     * @return bool the current authentication status
     */
    public function isAuthenticated()
    {
        $accessToken = $this->getOauthData(self::OAUTH_ACCESS_TOKEN);
        return !empty($accessToken);
    }

    /**
     * Return a value from the OAuth session data
     *
     * @param string $key
     * @return string value
     */
    public function getOauthData($key)
    {
        $val = NULL;
        $data = @$_SESSION[self::SESSION_OAUTH_DATA];
        if (is_array($data))
        {
            $val = @$data[$key];
        }
        return $val;
    }

    /**
     * Set a value for the OAuth session data
     *
     * @param string $key
     * @param string $value
     */
    private function setOauthData($key, $value)
    {
        $data = @$_SESSION[self::SESSION_OAUTH_DATA];
        if (!is_array($data))
        {
            $data = array();
        }
        $data[$key] = $value;
        $_SESSION[self::SESSION_OAUTH_DATA] = $data;
    }

    /**
     * Return the HTTP Response code for the last HTTP call made
     *
     * @return int
     */
    public function getLastHttpResponseCode()
    {
        return $this->lastHttpResponseCode;
    }

    /**
     * Set the timeout for HTTP requests
     *
     * @param int $timeout
     */
    public function setHttpTimeout($timeout)
    {
        $this->httpTimeout = $timeout;
    }

    /**
     * Check whether the current scopes satisfy those requested
     *
     * @param string $scopesRequired
     * @return bool
     */
    private function doWeHaveTheRightScope($scopesRequired)
    {
        $ok = false;

        $arrayOfCurrentScopes = explode(' ', $this->getOauthData(self::SCOPE));
        $arrayOfRequiredScopes = explode(' ', $scopesRequired);

        // So which scopes that we want do we already have?
        $activeScopes = array_intersect($arrayOfRequiredScopes, $arrayOfCurrentScopes);

        // If the intersection contains all the things we asked for, we're good to go
        if (count($activeScopes) == count($arrayOfRequiredScopes))
        {
            // Sounds good
            $ok = true;
        }

        return $ok;
    }

    /**
     * Get an access token from app.net
     *
     * @param string $code The code returned by the client authorisation callback
     * @return bool
     */
    private function obtainAccessToken($code)
    {
        $ok = false;

        $params = array(
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'authorization_code',
            'code' => $code,
        );

        $rsp = $this->httpRequest(self::ACCESS_TOKEN_ENDPOINT, $params, 'POST');

        if (!empty($rsp))
        {
            $data = json_decode($rsp);

            $accessToken = @$data->{'access_token'};
            if (!empty($accessToken))
            {
                $userId = @$data->{'user_id'};
                $userName = @$data->{'username'};
                $this->setOauthData(self::OAUTH_ACCESS_TOKEN, $accessToken);
                $this->setOauthData(self::USER_ID, $userId);
                $this->setOauthData(self::USER_NAME, $userName);
                $ok = true;
            }
        }

        return $ok;
    }

    /**
     * Join an array of parameters together into a URL-encoded string
     *
     * @param array $parameters
     * @return string
     */
    private function joinParameters($parameters)
    {
        $keys = array_keys($parameters);
        sort($keys, SORT_STRING);
        $keyValuePairs = array();
        foreach ($keys as $k)
        {
            array_push($keyValuePairs, rawurlencode($k) . "=" . rawurlencode($parameters[$k]));
        }

        return implode("&", $keyValuePairs);
    }

    /**
     * Make an HTTP request
     *
     * @param string $url
     * @param array $parameters
     * @return mixed
     */
    private function httpRequest($url, $parameters = NULL, $method = 'GET')
    {
        $param = ($parameters == NULL) ? array() : $parameters;

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->httpTimeout);

        $accessToken = $this->getOauthData(self::OAUTH_ACCESS_TOKEN);
        if (!empty($accessToken))
        {
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(sprintf("Authorization: Bearer %s", $accessToken)));
        }

        if ($method == 'POST')
        {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $param);
        }
        else
        {
            // Assume GET
            curl_setopt($curl, CURLOPT_URL, "$url?" . $this->joinParameters($param));
        }

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);

        curl_close($curl);

        $this->lastHttpResponseCode = $headers['http_code'];

        return $response;
    }
}
