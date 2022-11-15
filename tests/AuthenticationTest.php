<?php

namespace App\Tests;


use Symfony\Component\HttpFoundation\Response;

class AuthenticationTest extends AbstractControllerTest
{
    // successful auth is tested in all other tests

    public function testAuthenticationInvalidKey()
    {
        $this->client->setServerParameters([
            'HTTP_X-API-KEY' => 'some_invalid_key',
            'HTTP_Accept' => 'application/json',
        ]);
        $this->client->request('GET', '/api/users/');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testAuthenticationMissingKey()
    {
        $this->client->setServerParameters([
            'HTTP_Accept' => 'application/json',
        ]);
        $this->client->request('GET', '/api/users/');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
