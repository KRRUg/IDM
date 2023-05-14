<?php


namespace App\Tests;


use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class UserControllerUpdateTest extends AbstractControllerTest
{
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
        $this->client->request('PATCH', '/api/users/00000000-0000-0000-0000-000000000001', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
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
        $this->assertEquals("00000000-0000-0000-0000-000000000001", $result['uuid']);
        $this->assertEquals("user1@localhost.local", $result['email']);
        $this->assertEquals("User", $result['firstname']);
        $this->assertEquals("One", $result['surname']);

        // retry to check if the update was saved
        $this->client->request('GET', '/api/users/00000000-0000-0000-0000-000000000001');
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
        $this->assertEquals("00000000-0000-0000-0000-000000000001", $result['uuid']);
        $this->assertEquals("user1@localhost.local", $result['email']);
        $this->assertEquals("User", $result['firstname']);
        $this->assertEquals("One", $result['surname']);
    }

    public function testUserUpdateFailReadOnlyArgument()
    {
        $data = <<<JSON
{
    "email": "user1@localhost.local",
    "firstname": "User",
    "surname": "One",
    "nickname": "User 1",
    "registeredAt": "2021-01-06T23:05:18+01:00"
}
JSON;
        $this->client->request('PATCH', '/api/users/00000000-0000-0000-0000-000000000001', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserUpdateSuccessWithPassword()
    {
        $data = <<<JSON
{
    "email": "user1@localhost.local",
    "password": "new_secure_password"
}
JSON;
        $this->client->request('PATCH', '/api/users/00000000-0000-0000-0000-000000000001', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
        $user1 = json_decode($response->getContent(), true);

        $data = <<<JSON
{
    "name": "user1@localhost.local",
    "secret": "new_secure_password"
}
JSON;
        // try to log in with new PW
        $this->client->request('POST', '/api/users/authorize', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent(), "No valid JSON returned.");
        $user2 = json_decode($response->getContent(), true);
        $this->assertEquals($user1['uuid'], $user2['uuid']);
        $this->assertEquals($user1['email'], $user2['email']);
        $this->assertEquals($user1['nickname'], $user2['nickname']);
    }

    public function testUserUpdateFailTooShortPassword()
    {
        $data = <<<JSON
{
    "password": "pw"
}
JSON;
        $this->client->request('PATCH', '/api/users/00000000-0000-0000-0000-000000000001', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserUpdateAlreadyExistingNicknameSelfupdate()
    {
        // Set name User 1 to User 1
        $data = <<<JSON
{
    "nickname": "User 1"
}
JSON;
        $uuid = Uuid::fromInteger(1)->toString();
        $this->client->request('PATCH', '/api/users/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserUpdateAlreadyExistingName()
    {
        $data = <<<JSON
{
    "nickname": "User 2"
}
JSON;
        $uuid = Uuid::fromInteger(1)->toString();
        $this->client->request('PATCH', '/api/users/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserUpdateValidDate()
    {
        $data = <<<JSON
{
    "birthdate": "2022-07-02"
}
JSON;
        $uuid = Uuid::fromInteger(1)->toString();
        $this->client->request('PATCH', '/api/users/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserUpdateInvalidDate()
    {
        $data = <<<JSON
{
    "birthdate": "invalid"
}
JSON;
        $uuid = Uuid::fromInteger(1)->toString();
        $this->client->request('PATCH', '/api/users/' . $uuid, [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }
}