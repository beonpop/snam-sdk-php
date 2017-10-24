<?php

namespace SNAMClient;

use SNAMClient\OAuth\OAuth;

class Connection
{
    public $name;
    private $snam;
    private $connections = [];
    public $owner = [];
    private $data = [];
    private $oAuth;

    public function __construct($name)
    {
        if (empty(session_id())) {
            session_start();
        }

        $this->name = strtolower($name);

        if ($this->isOAuth()) {
            $this->owner = unserialize($_SESSION["snam-connection-" . $this->name]);
        }
    }

    public function setResource(SNAM $snam)
    {
        $this->snam = $snam;
        return $this;
    }

    public function getData()
    {
        $request = $this->snam->request('GET', 'app/connection');
        $content = $request->content();
        foreach ($content as $conn) {
            $this->connections[$conn["connection"]] = $conn;
        }

        if (isset($this->connections[$this->name])) {
            $this->data = $this->connections[$this->name];
        }

        return $this;
    }

    public function getOAuth()
    {
        return $this->oAuth = OAuth::connection($this);
    }

    public function isOAuth()
    {
        return isset($_SESSION["snam-connection-" . $this->name]);
    }

    public function unsetOAuth()
    {
        unset($_SESSION["snam-connection-" . $this->name]);
    }

    public function registerOAuth()
    {
        if (empty($this->oAuth)) {
            return false;
        }

        $this->owner = $this->oAuth->getOwner();
        $_SESSION["snam-connection-" . $this->name] = serialize($this->owner);
    }

    public function registerAccount($account)
    {
        if (empty($this->oAuth)) {
            return false;
        }

        $this->owner = $this->oAuth->setAccount($account);
        $_SESSION["snam-connection-" . $this->name] = serialize($this->owner);
    }

    public function __get($name)
    {
        if (!isset($this->data[$name])) {
            return null;
        }

        return $this->data[$name];
    }

    public function __set($name, $value = "")
    {
        $this->data[$name] = $value;
    }

    public function __isset($name)
    {
        return !empty($this->data[$name]);
    }
}
