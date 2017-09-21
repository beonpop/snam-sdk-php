<?php

namespace SNAMClient\OAuth;

class OAuth
{
    public static function connection($connection)
    {
        $class = "SNAMClient\\OAuth\\" . ucfirst(strtolower($connection->name));
        return new $class($connection);
    }
}
