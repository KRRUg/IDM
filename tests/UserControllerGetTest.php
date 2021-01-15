<?php


namespace App\Tests;


use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class UserControllerGetTest extends AbstractControllerTest
{
    public function testUserRequestSuccessful()
    {
        $uuid = Uuid::fromInteger(1)->toString();
        $this->client->request('GET', '/api/users/' . $uuid);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey("uuid", $result);
        $this->assertArrayHasKey("email", $result);
        $this->assertArrayHasKey("nickname", $result);
        $this->assertArrayHasKey("emailConfirmed", $result);
        $this->assertArrayHasKey("isSuperadmin", $result);
        $this->assertArrayHasKey("registeredAt", $result);
        $this->assertArrayHasKey("modifiedAt", $result);
        $this->assertArrayHasKey("infoMails", $result);
        $this->assertArrayNotHasKey("password", $result);

        $this->assertEquals("User 1", $result['nickname']);
        $this->assertEquals("user1@localhost.local", $result['email']);
        $this->assertIsArray($result['clans']);
        $this->assertFalse($result['isSuperadmin']);
    }

    public function testUserRequestFailNotFound()
    {
        $uuid = Uuid::fromInteger(30)->toString();
        $this->client->request('GET', '/api/users/' . $uuid);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserGetSuccessfulAll()
    {
        $this->client->request('GET', '/api/users');
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
        $this->assertEquals(21, $result['total']);
    }

    public function testUserGetSuccessfulFilter()
    {
        $this->client->request('GET', '/api/users', ['filter' => "User", "limit" => 5, "page" => 2]);
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
        $this->assertEquals(20, $result['total']);
        $this->assertEquals(5, $result['count']);
    }

    public function testUserGetSuccessfulFilterExact()
    {
        $this->client->request('GET', '/api/users', ['filter' => "User 1", "exact" => "true", "limit" => 5, "page" => 1]);
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
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['count']);
    }

    public function testUserGetSuccessfulFilterAdvanced()
    {
        $this->client->request('GET', '/api/users', ["filter" => ["nickname" => "User 1"], "exact" => "false"]);
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
        $this->assertEquals(11, $result['total']);
        $this->assertEquals(10, $result['count']);
    }

    public function testUserGetSuccessfulFilterAdvancedExact()
    {
        $this->client->request('GET', '/api/users', ["filter" => ["nickname" => "User 1"], "exact" => "true"]);
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
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['count']);
    }

    public function testUserGetFailFilterPageExceeding()
    {
        $this->client->request('GET', '/api/users', ['filter' => "User", "limit" => 5, "page" => 200]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserGetSuccessfulNothingFound()
    {
        $this->client->request('GET', '/api/users', ['filter' => "DoesNotExistInDatabase"]);
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

    public function testUserGetSuccessfulSort()
    {
        $this->client->request('GET', '/api/users', ['sort' => [ 'nickname' => 'desc']]);
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
        $this->assertEquals("User 9", $item1['nickname']);
        $this->assertEquals("user9@localhost.local", $item1['email']);
    }

    public function testUserGetSuccessfulFilterAndSort()
    {
        $this->client->request('GET', '/api/users', ['filter' => ['email' => '@localhost.local'], 'sort' => [ 'email' => 'desc']]);
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
        $this->assertEquals("User 9", $item1['nickname']);
        $this->assertEquals("user9@localhost.local", $item1['email']);
    }

    public function testUserGetFailDeletedUser()
    {
        $this->client->request('GET', '/api/users', ['filter' => ['email' => 'ghost@localhost.local']]);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertArrayHasKey("total", $result);
        $this->assertArrayHasKey("count", $result);
        $this->assertArrayHasKey("items", $result);
        $this->assertEquals(0, $result['total']);
        $this->assertEquals(0, $result['count']);
        $items = $result["items"];
        $this->assertIsArray($items);
        $this->assertEmpty($items);
    }
}