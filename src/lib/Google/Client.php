<?php declare(strict_types=1);

namespace Daniel\Vote\Google;

use Google_Client;
use Google_Service_Oauth2;

use Daniel\Vote\Model;

class Client {
    protected Google_Client $client;

    public function __construct(string $clientID, string $clientSecret, string $redirectUrl) {
        $this->client = new Google_Client();
        $this->client->setClientId($clientID);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUrl);
        $this->client->addScope("email");
        $this->client->addScope("profile");
    }

    public function getAuthUrl(): string {
        return $this->client->createAuthUrl();
    }

    public function login(Model $model): void {
        $token = $this->client->fetchAccessTokenWithAuthCode($_GET['code']);
        $this->client->setAccessToken($token['access_token']);

        $google_oauth = new Google_Service_Oauth2($this->client);
        $google_account_info = $google_oauth->userinfo->get();

        $model->login($google_account_info->email);
    }
}