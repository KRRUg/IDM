<?php


namespace App\Tests;


use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class UserControllerTest extends AbstractControllerTest
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
        $this->assertArrayHasKey("firstname", $result);
        $this->assertArrayHasKey("surname", $result);
        $this->assertArrayHasKey("postcode", $result);
        $this->assertArrayHasKey("city", $result);
        $this->assertArrayHasKey("street", $result);
        $this->assertArrayHasKey("country", $result);
        $this->assertArrayHasKey("phone", $result);
        $this->assertArrayHasKey("gender", $result);
        $this->assertArrayHasKey("emailConfirmed", $result);
        $this->assertArrayHasKey("isSuperadmin", $result);
        $this->assertArrayHasKey("steamAccount", $result);
        $this->assertArrayHasKey("registeredAt", $result);
        $this->assertArrayHasKey("modifiedAt", $result);
        $this->assertArrayHasKey("hardware", $result);
        $this->assertArrayHasKey("infoMails", $result);
        $this->assertArrayHasKey("statements", $result);
        $this->assertArrayHasKey("birthdate", $result);
        $this->assertArrayNotHasKey("password", $result);

        $this->assertEquals("User 1", $result['nickname']);
        $this->assertEquals("user1@localhost.local", $result['email']);
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

    public function testUserSearchSuccessful()
    {
        $data = <<<JSON
{
    "nickname": "User 1"
}
JSON;
        $this->client->request('POST', '/api/users/search', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(1, sizeof($result));
        $this->assertEquals("User 1", $result[0]['nickname']);
    }

    public function testUserSearchSuccessfulEmpty()
    {
        $data = <<<JSON
{
}
JSON;
        $this->client->request('POST', '/api/users/search', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(21, sizeof($result));
        $this->assertIsArray($result[0]);
        $this->assertArrayHasKey("uuid", $result[0]);
        $this->assertArrayHasKey("email", $result[0]);
        $this->assertArrayHasKey("nickname", $result[0]);
        $this->assertArrayHasKey("firstname", $result[0]);
        $this->assertArrayHasKey("surname", $result[0]);
        $this->assertArrayHasKey("postcode", $result[0]);
        $this->assertArrayHasKey("city", $result[0]);
        $this->assertArrayHasKey("street", $result[0]);
        $this->assertArrayHasKey("country", $result[0]);
        $this->assertArrayHasKey("phone", $result[0]);
        $this->assertArrayHasKey("gender", $result[0]);
        $this->assertArrayHasKey("emailConfirmed", $result[0]);
        $this->assertArrayHasKey("isSuperadmin", $result[0]);
        $this->assertArrayHasKey("steamAccount", $result[0]);
        $this->assertArrayHasKey("registeredAt", $result[0]);
        $this->assertArrayHasKey("modifiedAt", $result[0]);
        $this->assertArrayHasKey("hardware", $result[0]);
        $this->assertArrayHasKey("infoMails", $result[0]);
        $this->assertArrayHasKey("statements", $result[0]);
        $this->assertArrayHasKey("birthdate", $result[0]);
        $this->assertArrayNotHasKey("password", $result[0]);
    }

    public function testUserSearchFailEmpty()
    {
        $this->client->request('POST', '/api/users/search', [], [], ['CONTENT_TYPE' => 'application/json'], null);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserSearchFailInvalidCriteria()
    {
        $data = <<<JSON
{
    "invalid": "invalid"
}
JSON;
        $this->client->request('POST', '/api/users/search', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
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
        $this->client->request('GET', '/api/users', ['q' => "User", "limit" => 5, "page" => 2]);
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

    public function testUserGetSuccessfulNothingFound()
    {
        $this->client->request('GET', '/api/users', ['q' => "DoesNotExistInDatabase"]);
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

    public function testUserUpdateSuccessful()
    {
        $data = <<<JSON
{
    "email": "user1@localhost.local",
    "firstname": "User",
    "surname": "One",
    "nickname": "User 1"
}
JSON;
        $this->client->request('PUT', '/api/users/00000000-0000-0000-0000-000000000001', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}