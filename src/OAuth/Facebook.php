<?php

namespace SNAMClient\OAuth;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use Psr\Http\Message\ResponseInterface;

class Facebook extends AbstractProvider
{
        
    const BASE_FACEBOOK_URL = 'https://www.facebook.com/';
    const BASE_GRAPH_URL = 'https://graph.facebook.com/';
    
    protected $graphApiVersion = '2.10';
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
            'clientSecret'      => $connection->secret,
            'graphApiVersion'   => 'v2.10'
        ]);
    }

    public function setRedirectUri($url)
    {
        $this->redirectUri = $url . "?connection=facebook";
    }

    public function getBaseAuthorizationUrl()
    {
        return $this->getBaseFacebookUrl() . $this->graphApiVersion . '/dialog/oauth';
    }

    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->getBaseGraphUrl() . $this->graphApiVersion . '/oauth/access_token';
    }

    public function getDefaultScopes()
    {
        return explode(",", $this->connection->scope);
    }

    public function getAuthorizationUrl(array $options = [])
    {
        $url = parent::getAuthorizationUrl($options) . "&auth_type=rerequest&display=popup";
        $_SESSION['snam-facebook-oauth2state'] = parent::getState();
        return $url;
    }

    public function isGetCode()
    {
        if (!isset($_GET['code']) || !isset($_GET['connection']) || $_GET["connection"] != 'facebook') {
            return false;
        } elseif (empty($_GET['state']) || (($_GET['state'] !== $_SESSION['snam-facebook-oauth2state']))) {
            unset($_SESSION['snam-facebook-oauth2state']);
            return 'Invalid state.';
        }
        return true;
    }

    public function getAccessToken($grant = 'authorization_code', array $params = [])
    {
        $params['code'] = $_GET['code'];
        return $this->lastToken = parent::getAccessToken($grant, $params);
    }

    public function getToken()
    {
        if (!empty($this->lastToken)) {
            return (string)$this->lastToken;
        }

        if (!empty($this->connection->owner) && isset($this->connection->owner["token"])) {
            return (string)$this->connection->owner["token"];
        }

        return (string)self::getAccessToken();
    }

    public function getLongLivedAccessToken($accessToken)
    {
        $params = [
            'fb_exchange_token' => (string) $accessToken,
        ];
        return $this->getAccessToken('fb_exchange_token', $params);
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
            "connection" => "facebook",
            "email"      => isset($response["email"]) ? $response["email"] : "",
            "name"       => $response["name"],
            "username"   => $response['name'],
            "picture"    => $response["picture"]["data"]["url"],
            "link"       => $response["link"],
            "userid"     => $response['id'],
            "token"      => (string) $token
        ];
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        $fields = [
            'id', 'name', 'email', 'picture.type(large){url,is_silhouette}',
            'gender', 'locale', 'link', 'timezone', 'age_range'
        ];
        $appSecretProof = hash_hmac('sha256', $token->getToken(), $this->clientSecret);
        return $this->getBaseGraphUrl().$this->graphApiVersion.'/me?fields='.implode(',', $fields)
                        .'&access_token='.$token.'&appsecret_proof='.$appSecretProof;
    }

    public function getPermissions()
    {
        $token = $this->getToken();
        $url = $this->getBaseGraphUrl().$this->graphApiVersion.'/me/permissions?access_token='.$token;
        $request = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token);
        $response = $this->getParsedResponse($request);
        $permissionAccepted = [];
        foreach ($response["data"] as $permission) {
            if ($permission["status"] == "granted") {
                $permissionAccepted[] = $permission["permission"];
            }
        }

        return $permissionAccepted;
    }

    public function getAccounts()
    {
        if (!in_array('manage_pages', $this->getPermissions())) {
            return false;
        }

        $fields = [
            'id', 'name', 'emails', 'picture.type(large){url}', 'link', 'username', 'access_token'
        ];

        $token = $this->getToken();
        $url = $this->getBaseGraphUrl().$this->graphApiVersion.'/me/accounts?fields='.implode(',', $fields).'&access_token='.$token;
        $request = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token);
        $response = $this->getParsedResponse($request);

        $accountList = [];
        foreach ($response["data"] as $account) {
            $accountList[$account["id"]] = [
                "connection" => "facebook",
                "email"      => (isset($account["emails"]) && is_array($account["emails"])) ? $account["emails"][0] : "",
                "name"       => $account["name"],
                "username"   => $account['username'],
                "picture"    => $account["picture"]["data"]["url"],
                "link"       => $account["link"],
                "userid"     => $account['id'],
                "token"      => $account['access_token']
            ];
        }
        
        return $accountList;
    }

    public function setAccount($response)
    {
        return [
            "type"       => "page",
            "connection" => "facebook",
            "email"      => isset($response["emails"]) ? $response["emails"][0] : "",
            "name"       => $response["name"],
            "username"   => $response['username'],
            "picture"    => $response["picture"],
            "link"       => $response["link"],
            "userid"     => $response['userid'],
            "token"      => (string) $response["token"]
        ];
    }

    public function getOwner()
    {
        if (empty($this->lastToken)) {
            $this->getAccessToken();
        }

        return $this->getResourceOwner($this->lastToken);
    }

    public function getLogoutUrl()
    {
        $params = [
            'next' => $this->redirectUri,
            'access_token' => $this->getToken(),
        ];
        return 'https://www.facebook.com/logout.php?' . http_build_query($params, null, '&');
    }

    private function getBaseFacebookUrl()
    {
        return static::BASE_FACEBOOK_URL;
    }

    private function getBaseGraphUrl()
    {
        return static::BASE_GRAPH_URL;
    }
}
