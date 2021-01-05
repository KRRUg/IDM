<?php


namespace App\Tests;


use Sentry\Util\JSON;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerRegisterTest extends AbstractControllerTest
{
    public function testRegisterSuccessful()
    {
        $data = <<<JSON
{
    "email": "b@lup.com",
    "password": "foofoo",
    "nickname": "blup",
    "firstname": "foo",
    "surname": "baa",
    "infoMail": true
}
JSON;
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CREATED, $response->getStatusCode());
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

        $this->assertEquals("blup", $result['nickname']);
        $this->assertEquals("b@lup.com", $result['email']);
        $this->assertEquals("foo", $result['firstname']);
        $this->assertEquals("baa", $result['surname']);
        $this->assertFalse($result['isSuperadmin']);
        $this->assertFalse($result['emailConfirmed']);
        $this->assertTrue($result['infoMails']);
    }

    public function testRegisterFailInvalidEmail()
    {
        $data = <<<JSON
{
    "email": "blup.com",
    "password": "foofoo",
    "nickname": "blup",
    "firstname": "foo",
    "surname": "baa",
    "infoMail": true
}
JSON;
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function testRegisterFailMissingField()
    {
        // firstname missing
        $data = <<<JSON
{
    "email": "b@lup.com",
    "password": "foofoo",
    "nickname": "blup",
    "surname": "baa",
    "infoMail": true
}
JSON;
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function testRegisterFailExistingEmail()
    {
        // email existing
        $data = <<<JSON
{
    "email": "user1@localhost.local",
    "password": "foofoo",
    "nickname": "blup",
    "firstname": "foo",
    "surname": "baa",
    "infoMail": true
}
JSON;
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function testRegisterFailExistingNickname()
    {
        // nickname existing
        $data = <<<JSON
{
    "email": "user_one@localhost.local",
    "password": "foofoo",
    "nickname": "User 1",
    "firstname": "foo",
    "surname": "baa",
    "infoMail": true
}
JSON;
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function testRegisterFailInvalidTypeBool()
    {
        // infoMail type is invalid
        $data = <<<JSON
{
    "email": "b@lup.com",
    "password": "foofoo",
    "nickname": "blup",
    "firstname": "foo",
    "surname": "baa",
    "infoMail": "of course"
}
JSON;
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }

    public function testRegisterFailInvalidTypeString()
    {
        // nickname type is invalid
        $data = <<<JSON
{
    "email": "b@lup.com",
    "password": "foofoo",
    "nickname": 1,
    "firstname": "foo",
    "surname": "baa",
    "infoMail": true
}
JSON;
        $this->client->request('POST', '/api/auth/register', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());
    }
}