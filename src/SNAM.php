<?php

namespace SNAMClient;

use SNAMClient\Response;
use SNAMClient\Request;

class SNAM
{
    private $host =  "https://%s.snam.io";
    private $app     = null;
    private $appId    = null;
    private $appToken = null;
    private $version  = "v2.1";

    public function __construct($config = [])
    {
        $this->app = isset($config["app"]) ? $config["app"] : getenv('SNAM_APP');
        $this->appId = isset($config["app_id"]) ? $config["app_id"] : getenv('SNAM_APP_ID');
        $this->appToken = isset($config["app_token"]) ? $config["app_token"] : getenv('SNAM_APP_TOKEN');
        $this->version = isset($config["version"]) ? $config["version"] : getenv('SNAM_VERSION');
        $this->host = sprintf($this->host, $this->app);
    }

    public function setHost($host = null, $version = null)
    {
        $this->host = $host;
        if (!empty($version)) {
            $this->version = $version;
        }
    }

    public function request($method, $command, $paramets = "")
    {
        $request = new Request($this->host . "/" . $this->version . "/" . $command);
        $request->setAuth($this->appId, $this->appToken);
        return $request->run($method, $paramets);
    }

    public function getConnection($name)
    {
        $conn = new Connection($name);
        $conn->setResource($this)->getData();
        return $conn;
    }
}
