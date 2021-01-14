<?php

namespace App\Tests;

use Symfony\Component\HttpFoundation\Response;

class UserControllerAuthorizeTest extends AbstractControllerTest
{
    public function testAuthorizeSuccessful()
    {
        $data = <<<JSON
{
    "name": "user1@localhost.local",
    "secret": "user1"
}
JSON;
        $this->client->request('POST', '/api/users/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testAuthorizeFailPasswordIncorrect()
    {
        $data = <<<JSON
{
    "name": "user1@localhost.local",
    "secret": "incorrect"
}
JSON;
        $this->client->request('POST', '/api/users/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
        $this->assertStringContainsStringIgnoringCase("invalid", $response->getContent());
    }

    public function testAuthorizeFailPasswordMissing()
    {
        $data = <<<JSON
{
    "name": "user1@localhost.local"
}
JSON;
        $this->client->request('POST', '/api/users/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
        $this->assertStringContainsStringIgnoringCase("invalid", $response->getContent());
    }

    public function testAuthorizeFailIncorrectData()
    {
        $data = <<<JSON
{
    "name": "user1@localhost.local",
    "secret": true
}
JSON;
        $this->client->request('POST', '/api/users/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function testAuthorizeFailAdditionalFields()
    {
        $data = <<<JSON
{
    "name": "user1@localhost.local",
    "secret": "user1",
    "override": "yes"
}
JSON;
        $this->client->request('POST', '/api/users/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }
}