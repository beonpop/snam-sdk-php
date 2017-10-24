<?php

namespace SNAMClient\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;
use Abraham\TwitterOAuth\TwitterOAuth;

class Twitter extends AbstractProvider
{
        
    const BASE_API_URL = 'https://api.twitter.com';
    const BASE_TWITTER_URL = 'https://www.twitter.com/';
    private $connection;
    private $lastToken;

    public function __construct($connection)
    {
        if (empty(session_id())) {
            session_start();
        }

        $this->connection = $connection;
    }

    public function setRedirectUri($url)
    {
        $this->redirectUri = $url . "?connection=twitter";
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->getBaseTwitterUrl() . '/oauth/request_token';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getBaseTwitterUrl() . '/oauth/access_token';
    }

    public function getAuthorizationUrl(array $options = [])
    {
        $connection = new TwitterOAuth($this->connection->key, $this->connection->secret);
        $requestToken = $connection->oauth('oauth/request_token', array('oauth_callback' => $this->redirectUri));

        $_SESSION["snam-twitter-oauthstate"] = json_encode($requestToken);
        
        return $connection->url('oauth/authorize', array('oauth_token' => $requestToken['oauth_token']));
    }

    public function isGetCode()
    {
        if (!isset($_GET['oauth_token']) || !isset($_SESSION["snam-twitter-oauthstate"])) {
            unset($_SESSION['snam-twitter-oauthstate']);
            return false;
        }

        if (!isset($_GET['connection']) || $_GET["connection"] != 'twitter') {
            return false;
        }

        $token = json_decode($_SESSION["snam-twitter-oauthstate"], true);
        if (isset($_GET['oauth_token']) && ($_GET['oauth_token'] !== $token['oauth_token'])) {
            unset($_SESSION['snam-twitter-oauthstate']);
            return 'Invalid state.';
        }

        return true;
    }

    public function clean()
    {
        unset($_SESSION['snam-twitter-oauthstate']);
    }

    public function getDefaultScopes()
    {
    }

    public function getAccessToken($grant = 'authorization_code', array $params = [])
    {
        $requestToken = json_decode($_SESSION["snam-twitter-oauthstate"], true);

        $connection = new TwitterOAuth(
            $this->connection->key,
            $this->connection->secret,
            $requestToken['oauth_token'],
            $requestToken['oauth_token_secret']
        );
        
        return $this->lastToken = $connection->oauth(
            "oauth/access_token",
            ["oauth_verifier" => $_GET['oauth_verifier']]
        );
    }

    protected function checkResponse(ResponseInterface $response, $data)
    {
    }

    protected function createResourceOwner(array $response, AccessToken $token)
    {
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
    }

    public function getOwner()
    {
        if (empty($this->lastToken)) {
            $this->getAccessToken();
        }

        $connection = new TwitterOAuth(
            $this->connection->key,
            $this->connection->secret,
            $this->lastToken['oauth_token'],
            $this->lastToken['oauth_token_secret']
        );
            
        $user = $connection->get("account/verify_credentials");

        return [
            "connection" => "twitter",
            "email"      => "",
            "name"       => $user->name,
            "username"   => $user->screen_name,
            "userid"     => $user->id,
            "picture"    => $user->profile_image_url,
            "link"       => static::BASE_TWITTER_URL . '/' . $user->screen_name,
            "token"      => [
                "oauth_token" => $this->lastToken['oauth_token'],
                "oauth_token_secret" => $this->lastToken['oauth_token_secret']
            ]
        ];
    }

    private function getBaseTwitterUrl()
    {
        return static::BASE_API_URL;
    }
}
