<?php


namespace App\Tests;


use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class ClanControllerMemberTest extends AbstractControllerTest
{
    public function testGetMembers()
    {
        $uuid = Uuid::fromInteger(1001)->toString();
        $this->client->request('GET', '/api/clans/' . $uuid . "/users");

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());

        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(2, sizeof($result));
    }

    public function testAddMember()
    {
        $uuid = Uuid::fromInteger(1003)->toString();
        $user = Uuid::fromInteger(18)->toString();
        $data = <<<JSON
{
    "uuid": "{$user}"
}
JSON;

        $this->client->request('POST', '/api/clans/' . $uuid . "/users", [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // check if user is not an admin now
        $this->client->request('GET', '/api/clans/' . $uuid . "/admins");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(0, sizeof($result));

        // check if user is actually a member now
        $this->client->request('GET', '/api/clans/' . $uuid . "/users");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(1, sizeof($result));
    }

    public function testAddAdmin()
    {
        $uuid = Uuid::fromInteger(1003)->toString();
        $user = Uuid::fromInteger(18)->toString();
        $data = <<<JSON
{
    "uuid": "{$user}"
}
JSON;

        $this->client->request('POST', '/api/clans/' . $uuid . "/admins", [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // check if user is an admin now
        $this->client->request('GET', '/api/clans/' . $uuid . "/admins");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(1, sizeof($result));

        // check if user is actually a member now
        $this->client->request('GET', '/api/clans/' . $uuid . "/users");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(1, sizeof($result));
    }
}