<?php


namespace App\Tests;


use Symfony\Component\HttpFoundation\Response;

class ClanControllerBulkTest extends AbstractControllerTest
{
    public function testClanBulkSuccessful()
    {
        $data = <<<JSON
{
        "uuid": ["00000000-0000-0000-0000-0000000003e9", "00000000-0000-0000-0000-0000000003ea"]
}
JSON;
        $this->client->request('POST', '/api/clans/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");

        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(2, sizeof($result));
        $this->assertIsArray($result[0]);
        $this->assertIsArray($result[1]);
        $this->assertEquals("Clan 1", $result[0]['name']);
        $this->assertEquals("Clan 2", $result[1]['name']);
    }

    public function testClanBulkSuccessfulEmpty()
    {
        $data = <<<JSON
{
    "uuid": []
}
JSON;
        $this->client->request('POST', '/api/clans/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(0, sizeof($result));
    }

    public function testClanBulkFailEmpty()
    {
        $this->client->request('POST', '/api/clans/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], null);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testClanBulkFailInvalidCriteria1()
    {
        $data = <<<JSON
{
    "invalid": "invalid"
}
JSON;
        $this->client->request('POST', '/api/clans/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testClanBulkFailInvalidCriteria2()
    {
        $data = <<<JSON
{
    "uuid": "invalid"
}
JSON;
        $this->client->request('POST', '/api/clans/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }

    public function testClanBulkFailInvalidCriteria3()
    {
        $data = <<<JSON
{
    "uuid": ["invalid"]
}
JSON;
        $this->client->request('POST', '/api/clans/bulk', [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent(), "No valid JSON returned.");
    }
}