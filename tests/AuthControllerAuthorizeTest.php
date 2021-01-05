<?php


namespace App\Tests;


use Sentry\Util\JSON;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerAuthorizeTest extends AbstractControllerTest
{
    public function testAuthorizeSuccessful()
    {
        $data = <<<JSON
{
    "email": "user1@localhost.local",
    "password": "user1"
}
JSON;
        $this->client->request('POST', '/api/auth/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertEmpty($response->getContent(), "No data expected");
    }

    public function testAuthorizeFailPasswordIncorrect()
    {
        $data = <<<JSON
{
    "email": "user1@localhost.local",
    "password": "incorrect"
}
JSON;
        $this->client->request('POST', '/api/auth/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
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
    "email": "user1@localhost.local"
}
JSON;
        $this->client->request('POST', '/api/auth/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
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
    "email": "user1@localhost.local",
    "password": true
}
JSON;
        $this->client->request('POST', '/api/auth/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function testAuthorizeFailAdditionalFields()
    {
        $data = <<<JSON
{
    "email": "user1@localhost.local",
    "password": "user1",
    "override": "yes"
}
JSON;
        $this->client->request('POST', '/api/auth/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }
}