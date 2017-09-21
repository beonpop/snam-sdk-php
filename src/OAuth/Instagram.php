<?php

namespace SNAMClient\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class Instagram extends AbstractProvider
{
        
    const BASE_API_URL = 'https://api.instagram.com';
    const BASE_INSTAGRAM_URL = 'https://www.instagram.com/';
    protected $apiVersion = 'v1';
    private $connection;
    private $lastToken;

    public function __construct($connection)
    {
        if (empty(session_id())) {
            session_start();
        }

        $this->connection = $connection;
        parent::__construct([
            'clientId'          => $connection->clientid,
            'clientSecret'      => $connection->secret
        ]);
    }

    public function setRedirectUri($url)
    {
        $this->redirectUri = $url . "?connection=instagram";
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->getBaseInstagramUrl() . '/oauth/authorize';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getBaseInstagramUrl() . '/oauth/access_token';
    }

    public function getDefaultScopes()
    {
        return explode(",", $this->connection->scope);
    }

    public function getAuthorizationUrl(array $options = [])
    {
        $url = parent::getAuthorizationUrl($options) . "&force_classic_login=";
        $_SESSION['snam-instagram-oauth2state'] = parent::getState();
        return $url;
    }

    public function isGetCode()
    {
        if (!isset($_GET['code']) || !isset($_GET['connection']) || $_GET["connection"] != 'instagram') {
            return false;
        } elseif (empty($_GET['state']) || (($_GET['state'] !== $_SESSION['snam-instagram-oauth2state']))) {
            unset($_SESSION['snam-instagram-oauth2state']);
            return 'Invalid state.';
        }
        return true;
    }

    public function getAccessToken($grant = 'authorization_code', array $params = [])
    {
        if (isset($_GET['code'])) {
            $params['code'] = $_GET['code'];
        }
        return $this->lastToken = parent::getAccessToken($grant, $params);
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (!empty($data['error'])) {
            $message = $data['error']['type'].': '.$data['error']['message'];
            throw new IdentityProviderException($message, $data['error']['code'], $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return [
            "connection" => "instagram",
            "email"      => null,
            "name"       => $response["data"]["full_name"],
            "username"   => $response["data"]["username"],
            "picture"    => $response["data"]["profile_picture"],
            "link"       => static::BASE_INSTAGRAM_URL . '/' . $response["data"]["username"],
            "userid"     => $response["data"]["id"],
            "token"      => (string) $token
        ];
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getBaseInstagramUrl() . '/' . $this->apiVersion . '/users/self?access_token=' . $token->getToken();
    }

    public function getOwner()
    {
        if (empty($this->lastToken)) {
            $this->getAccessToken();
        }

        return $this->getResourceOwner($this->lastToken);
    }

    private function getBaseInstagramUrl()
    {
        return static::BASE_API_URL;
    }
}
