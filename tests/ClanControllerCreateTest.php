<?php

namespace App\Tests;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class ClanControllerCreateTest extends AbstractControllerTest
{
    public function testClanCreateSuccess()
    {
        $data = <<<JSON
{
    "name": "Clan 1337",
    "clantag": "<1337>",
    "joinPassword": "foofoo",
    "description": "we are 1337",
    "website": "https://1337.io"
}
JSON;
        $this->client->request('POST', '/api/clans', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
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

        $this->assertEquals("Clan 1337", $result['name']);
        $this->assertEquals("<1337>", $result['clantag']);
        $this->assertEquals("https://1337.io", $result['website']);
        $this->assertEquals("we are 1337", $result['description']);
        $this->assertIsArray($result["users"]);
        $this->assertIsArray($result["admins"]);
    }

    public function testClanCreateFailMissingField()
    {
        // firstname missing
        $data = <<<JSON
{
    "name": "Clan 1337",
    "joinPassword": "foofoo"
}
JSON;
        $this->client->request('POST', '/api/clans', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function testClanCreateFailExistingTag()
    {
        // email existing
        $data = <<<JSON
{
    "name": "Clan One",
    "clantag": "CL1",
    "joinPassword": "foofoo"
}
JSON;
        $this->client->request('POST', '/api/clans', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }
}