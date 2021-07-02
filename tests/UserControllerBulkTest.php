<?php


namespace App\Tests;


use Symfony\Component\HttpFoundation\Response;

class UserControllerBulkTest extends AbstractControllerTest
{
    public function testUserBulkSuccessful()
    {
        $data = <<<JSON
{
    "uuid": ["00000000-0000-0000-0000-000000000001", "00000000-0000-0000-0000-000000000003", "00000000-0000-0000-0000-000000000005"]
}
JSON;
        $this->client->request('POST', '/api/users/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(3, sizeof($result));
        $this->assertIsArray($result[0]);
        $this->assertIsArray($result[1]);
        $this->assertIsArray($result[2]);
        $this->assertEquals("User 1", $result[0]['nickname']);
        $this->assertEquals("User 3", $result[1]['nickname']);
        $this->assertEquals("User 5", $result[2]['nickname']);
    }

    public function testUserBulkSuccessfulEmpty()
    {
        $data = <<<JSON
{
    "uuid": []
}
JSON;
        $this->client->request('POST', '/api/users/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(0, sizeof($result));
    }

    public function testUserBulkFailEmpty()
    {
        $this->client->request('POST', '/api/users/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], null);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserBulkFailInvalidCriteria1()
    {
        $data = <<<JSON
{
    "invalid": "invalid"
}
JSON;
        $this->client->request('POST', '/api/users/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserBulkFailInvalidCriteria2()
    {
        $data = <<<JSON
{
    "uuid": "invalid"
}
JSON;
        $this->client->request('POST', '/api/users/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testUserBulkFailInvalidCriteria3()
    {
        $data = <<<JSON
{
    "uuid": ["invalid"]
}
JSON;
        $this->client->request('POST', '/api/users/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }
}