<?php

declare(strict_types=1);

namespace Daniel\Vote\Google;

use Google_Client;
use Google_Service_Oauth2;

use Daniel\Vote\Model;

class Client
{
    protected Google_Client $client;

    /**
     * @param string $clientID
     * @param string $clientSecret
     * @param string $redirectUrl
     */
    public function __construct(string $clientID, string $clientSecret, string $redirectUrl)
    {
        $this->client = new Google_Client();
        $this->client->setClientId($clientID);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUrl);
        $this->client->addScope("email");
        $this->client->addScope("profile");
    }

    /**
     * @return string
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Validates Google SSO and login SSO's email.
     *
     * @param Model $model
     */
    public function login(Model $model): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
        $this->client->setAccessToken($token['access_token']);

        $google_oauth = new Google_Service_Oauth2($this->client);
        $google_account_info = $google_oauth->userinfo->get();

        $model->login($google_account_info->email);
    }
}
