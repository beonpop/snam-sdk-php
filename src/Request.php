<?php

namespace SNAMClient;

class Request
{
    private $header = [];
    private $url = "";
    private $verbose = false;
    private $timeout = 180;
    private $connectTimeout = 60;
    private $authBasic;

    public function __construct($url)
    {
        $this->url = $url;
        $this->addHeader('Content-Type', 'application/json');
    }
    
    public function addHeader($type, $content)
    {
        $this->header[] = $type . ": " . $content;
    }

    public function run($method, $paramets = "")
    {
        $curl = curl_init();
        if (is_array($paramets)) {
            $requestBody = json_encode($paramets);
        } else {
            $requestBody = $paramets;
        }

        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $this->authBasic);

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
    
    public function setAuth($user, $pwd)
    {
        $this->authBasic = $user . ':' . $pwd;
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
