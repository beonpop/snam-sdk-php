<?php

namespace SNAMClient;

use SNAMClient\Response;

class SNAM
{
    private $host =  "https://%s.snam.io";
    private $app     = null;
    private $appId    = null;
    private $appToken = null;
    private $version  = "v2.1";
    private $header = [];
    private $url = "";

    public $verbose = false;
    public $timeout = 180;
    public $connectTimeout = 60;

    public function __construct($config = [])
    {
        $this->app = isset($config["app"]) ? $config["app"] : getenv('SNAM_APP');
        $this->appId = isset($config["app_id"]) ? $config["app_id"] : getenv('SNAM_APP_ID');
        $this->appToken = isset($config["app_token"]) ? $config["app_token"] : getenv('SNAM_APP_TOKEN');
        $this->version = isset($config["version"]) ? $config["version"] : getenv('SNAM_VERSION');
        $this->addHeader('Content-Type', 'application/json');
        $this->host = sprintf($this->host, $this->app);
    }

    public function setHost($host = null, $version = null)
    {
        $this->host = $host;
        if (!empty($version)) {
            $this->version = $version;
        }
    }

    public function addHeader($type, $content)
    {
        $this->header[] = $type . ": " . $content;
    }

    public function request($method, $command, $paramets = "")
    {
        $curl = curl_init();
        if (is_array($paramets)) {
            $requestBody = json_encode($paramets);
        } else {
            $requestBody = $paramets;
        }

        $this->url = $this->host . "/" . $this->version . "/" . $command;

        $this->setAuth($curl);
        
        switch (strtoupper($method)) {
            case 'GET':
                return $this->_get($curl, $requestBody);
            case 'POST':
                return $this->_post($curl, $requestBody);
            case 'PUT':
                return $this->_put($curl, $requestBody);
            case 'DELETE':
                return $this->_delete($curl);
            default:
                throw new Exception('Current method (' . $this->method . ') is invalid');
        }
    }

    private function doRequest($curl)
    {
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
        
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_VERBOSE, $this->verbose);

        if (count($this->header) > 0) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header);
        }

        $curlErrno = curl_errno($curl);
        $curlError = curl_error($curl);

        curl_close($curl);

        if (!empty($curlErrno)) {
            throw new Exception(
                "Failed Access to Web Service : Error: $curlError ($curlErrno)"
            );
        }

        return new Response($curl);
    }
    
    private function setAuth($curl)
    {
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $this->appId . ':' . $this->appToken);
    }

    private function _get($curl, $requestBody)
    {
        $this->url .= "?" . $requestBody;
        return $this->doRequest($curl);
    }

    private function _post($curl, $requestBody)
    {
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
        curl_setopt($curl, CURLOPT_POST, 1);
        return $this->doRequest($curl);
    }

    private function _put($curl, $requestBody)
    {
        $fh = fopen('php://memory', 'rw');
        fwrite($fh, $requestBody);
        rewind($fh);
        curl_setopt($curl, CURLOPT_INFILE, $fh);
        curl_setopt($curl, CURLOPT_INFILESIZE, strlen($requestBody));
        curl_setopt($curl, CURLOPT_PUT, true);
        return $this->doRequest($curl);
    }

    private function _delete($curl)
    {
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        return $this->doRequest($curl);
    }
}
