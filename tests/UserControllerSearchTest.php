<?php


namespace App\Tests;


use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class UserControllerSearchTest extends AbstractControllerTest
{
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
        $this->assertArrayHasKey("emailConfirmed", $result[0]);
        $this->assertArrayHasKey("isSuperadmin", $result[0]);
        $this->assertArrayHasKey("registeredAt", $result[0]);
        $this->assertArrayHasKey("modifiedAt", $result[0]);
        $this->assertArrayHasKey("infoMails", $result[0]);
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
}