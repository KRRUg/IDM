<?php

namespace App\Tests;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class ClanControllerGetTest extends AbstractControllerTest
{
    public function testClanRequestSuccessful()
    {
        $uuid = Uuid::fromInteger(1001)->toString();
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

        $this->assertEquals("Clan 1", $result['name']);
        $this->assertEquals("CL1", $result['clantag']);
        $this->assertIsArray($result["users"]);
        $this->assertIsArray($result["admins"]);
        $this->assertGreaterThan(0, sizeof($result["users"]));
        $this->assertGreaterThan(0, sizeof($result["admins"]));
    }

    public function testClanRequestFailNotFound()
    {
        $uuid = Uuid::fromInteger(30)->toString();
        $this->client->request('GET', '/api/clans/' . $uuid);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testClanGetSuccessfulAll()
    {
        $this->client->request('GET', '/api/clans');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey("total", $result);
        $this->assertArrayHasKey("count", $result);
        $this->assertArrayHasKey("items", $result);
        $items = $result["items"];
        $this->assertIsArray($items);
        $this->assertIsNumeric($result['total']);
        $this->assertIsNumeric($result['count']);
        $this->assertEquals(3, $result['total']);
    }

    public function testClanGetSuccessfulFilter()
    {
        $this->client->request('GET', '/api/clans', ['q' => "Clan", "limit" => 5, "page" => 1]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey("total", $result);
        $this->assertArrayHasKey("count", $result);
        $this->assertArrayHasKey("items", $result);
        $items = $result["items"];
        $this->assertIsArray($items);
        $this->assertIsNumeric($result['total']);
        $this->assertIsNumeric($result['count']);
        $this->assertEquals(3, $result['total']);
        $this->assertEquals(3, $result['count']);
    }

    public function testClanGetFailFilterPageExceeding()
    {
        $this->client->request('GET', '/api/clans', ['q' => "Clan", "limit" => 5, "page" => 200]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testClanGetSuccessfulNothingFound()
    {
        $this->client->request('GET', '/api/clans', ['q' => "DoesNotExistInDatabase"]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey("total", $result);
        $this->assertArrayHasKey("count", $result);
        $this->assertArrayHasKey("items", $result);
        $items = $result["items"];
        $this->assertIsArray($items);
        $this->assertEmpty($items);
        $this->assertIsNumeric($result['total']);
        $this->assertIsNumeric($result['count']);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['count']);
    }

    public function testClanGetSuccessfulSort()
    {
        $this->client->request('GET', '/api/clans', ['sort' => [ 'name' => 'desc']]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey("total", $result);
        $this->assertArrayHasKey("count", $result);
        $this->assertArrayHasKey("items", $result);
        $items = $result["items"];
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);
        $this->assertIsNumeric($result['total']);
        $this->assertIsNumeric($result['count']);
        $item1 = $items[0];
        $this->assertEquals("Clan 3", $item1['name']);
    }

    public function testClanGetSuccessfulFilterAndSort()
    {
        $this->client->request('GET', '/api/clans', ['filter' => ['name' => 'Clan'], 'sort' => [ 'clantag' => 'desc']]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey("total", $result);
        $this->assertArrayHasKey("count", $result);
        $this->assertArrayHasKey("items", $result);
        $items = $result["items"];
        $this->assertIsArray($items);
        $this->assertNotEmpty($items);
        $this->assertIsNumeric($result['total']);
        $this->assertIsNumeric($result['count']);
        $item1 = $items[0];
        $this->assertEquals("Clan 3", $item1['name']);
        $this->assertEquals("CL3", $item1['clantag']);
    }
}