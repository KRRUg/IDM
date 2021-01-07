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
        $uuid = Uuid::fromInteger(1001)->toString();
        $user = Uuid::fromInteger(4)->toString();
        $data = "\"{$user}\"";

        $this->client->request('POST', '/api/clans/' . $uuid . "/users", [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }
}