<?php

namespace SNAMClient\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class Soundcloud extends AbstractProvider
{
        
    const BASE_API_URL = 'https://api.soundcloud.com';
    const BASE_SOUNDCLOUD_URL = 'https://www.soundcloud.com';
    private $connection;
    private $lastToken;

    public function __construct($connection)
    {
        if (empty(session_id())) {
            session_start();
        }

        $this->connection = $connection;
        parent::__construct([
            'clientId'          => $this->connection->clientid,
            'clientSecret'      => $this->connection->secret
        ]);
    }

    public function setRedirectUri($url)
    {
        $this->redirectUri = $url;
    }

    public function getBaseAuthorizationUrl()
    {
        return static::BASE_SOUNDCLOUD_URL . '/connect';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getBaseSoundCloudUrl() . '/oauth2/token';
    }

    public function getAuthorizationUrl(array $options = [])
    {
        $this->getAuthorizationParameters($options);
        $state = parent::getState();
        $params = sprintf(
            "?client_id=%s&redirect_uri=%s&response_type=code_and_token&state=%s",
            $this->connection->clientid,
            $this->redirectUri,
            $state
        );
        $_SESSION['snam-soundcloud-oauth2state'] =  $state;
        return $this->getBaseAuthorizationUrl() . $params;
    }

    public function getDefaultScopes()
    {
    }

    public function isGetCode()
    {
        if (empty($_GET['state']) || (($_GET['state'] !== $_SESSION['snam-soundcloud-oauth2state']))) {
            unset($_SESSION['snam-soundcloud-oauth2state']);
            return 'Invalid state.';
        }
        return true;
    }

    public function getAccessToken($grant = 'authorization_code', array $params = [])
    {
        if (isset($_GET['code'])) {
            $params['code'] = $_GET['code'];
        }

        return $this->lastToken = parent::getAccessToken($grant, $params );
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
        if (isset($data['error'])) {
            throw new IdentityProviderException("ERROR:" . $data['error'], 500, $data);
        }
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return [
            "connection" => "soundcloud",
            "email"      => null,
            "name"       => $response["full_name"],
            "username"   => $response["username"],
            "picture"    => $response["avatar_url"],
            "link"       => $response["permalink_url"],
            "userid"     => $response["id"],
            "token"      => (string) $token
        ];
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->getBaseSoundCloudUrl() . '/me?oauth_token=' . $token->getToken();
    }

    public function getOwner()
    {
        if (empty($this->lastToken)) {
            $this->getAccessToken();
        }

        return $this->getResourceOwner($this->lastToken);
    }

    private function getBaseSoundCloudUrl()
    {
        return static::BASE_API_URL;
    }
}
