<?php

use SNAMClient\SNAM;

class SNAMTest extends PHPUnit_Framework_TestCase
{

    public function testConnect()
    {

        $client = new SNAM([
            "app_id" => getenv('SNAM_APP_ID'),
            "app_token" => getenv('SNAM_APP_TOKEN'),
            "app" => getenv('SNAM_APP'),
            "version" => 'v2.1'
        ]);

        $r = $client->request('GET', 'version');

        $this->assertEquals('2.1', $r->content());
        $this->assertEquals(200, $r->code());
        $this->assertEquals('OK', $r->message());

        $user = Faker\Factory::create();

        $data = [
            "name" => $user->name,
            "external_id" => $user->randomNumber,
            "email" => $user->safeEmail,
            "connections" => [
                [
                    "connection" => "facebook",
                    "userid"     => '100002492169441',
                    "token"      => "CAAH3ZAANUFvsBABl....."
                ]
            ]
        ];

        $r = $client->request('POST', 'auth', $data);
        var_dump($r->headers());
        var_dump($r->content());
    }
}
