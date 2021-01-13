<?php

namespace App\Tests;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class ClanControllerUpdateTest extends AbstractControllerTest
{
    public function testClanUpdateSuccessful()
    {
        $data = <<<JSON
{
    "name": "Clan 1337",
    "clantag": "<1337>"
}
JSON;
        $uuid = Uuid::fromInteger(1001)->toString();
        $this->client->request('PATCH', '/api/clans/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey("uuid", $result);
        $this->assertArrayHasKey("name", $result);
        $this->assertArrayHasKey("clantag", $result);
        $this->assertArrayHasKey("description", $result);
        $this->assertArrayHasKey("website", $result);
        $this->assertArrayHasKey("createdAt", $result);
        $this->assertArrayHasKey("modifiedAt", $result);
        $this->assertArrayNotHasKey("joinPassword", $result);
        $this->assertEquals($uuid, $result['uuid']);
        $this->assertEquals("Clan 1337", $result['name']);
        $this->assertEquals("<1337>", $result['clantag']);

        // retry to check if the update was saved
        $this->client->request('GET', '/api/clans/' . $uuid);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey("uuid", $result);
        $this->assertArrayHasKey("name", $result);
        $this->assertArrayHasKey("clantag", $result);
        $this->assertArrayHasKey("description", $result);
        $this->assertArrayHasKey("website", $result);
        $this->assertArrayHasKey("createdAt", $result);
        $this->assertArrayHasKey("modifiedAt", $result);
        $this->assertArrayNotHasKey("joinPassword", $result);
        $this->assertEquals($uuid, $result['uuid']);
        $this->assertEquals("Clan 1337", $result['name']);
        $this->assertEquals("<1337>", $result['clantag']);
    }

    public function testClanUpdateFailReadOnlyArgument()
    {
        $data = <<<JSON
{
    "name": "Clan 1337",
    "clantag": "<1337>"
    "createdAt": "2021-01-06T23:05:18+01:00"
}
JSON;
        $uuid = Uuid::fromInteger(1001)->toString();
        $this->client->request('PATCH', '/api/clans/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserUpdateSuccessWithPassword()
    {
        $data = <<<JSON
{
    "clantag": "Clan 1337",
    "joinPassword": "new_secure_password"
}
JSON;
        $uuid = Uuid::fromInteger(1001)->toString();
        $this->client->request('PATCH', '/api/clans/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserUpdateFailTooShortPassword()
    {
        $data = <<<JSON
{
    "joinPassword": "pw"
}
JSON;
        $uuid = Uuid::fromInteger(1001)->toString();
        $this->client->request('PATCH', '/api/clans/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testClanUpdateAlreadyExistingNameSelfupdate()
    {
        // Set name Clan 1 to Clan 1
        $data = <<<JSON
{
    "name": "Clan 1"
}
JSON;
        $uuid = Uuid::fromInteger(1001)->toString();
        $this->client->request('PATCH', '/api/clans/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testClanUpdateAlreadyExistingName()
    {
        $data = <<<JSON
{
    "name": "Clan 2"
}
JSON;
        $uuid = Uuid::fromInteger(1001)->toString();
        $this->client->request('PATCH', '/api/clans/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }
}