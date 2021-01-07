<?php

namespace App\Tests;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class ClanControllerDeleteTest extends AbstractControllerTest
{
    public function testClanDeleteSuccess()
    {
        $uuid = Uuid::fromInteger(1001)->toString();
        $this->client->request('DELETE', '/api/clans/' . $uuid);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmpty($response->getContent());

        $this->client->request('GET', '/api/clans/' . $uuid);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testClanDeleteFailNotFound()
    {
        $uuid = Uuid::fromInteger(2002)->toString();
        $this->client->request('DELETE', '/api/clans/' . $uuid);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }
}