<?php


namespace App\Tests;

use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;

class ClanControllerMemberTest extends AbstractControllerTest
{
    public function testGetMembersSuccess()
    {
        $uuid = Uuid::fromInteger(1001)->toString();
        $this->client->request('GET', '/api/clans/' . $uuid . "/users");

        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertEquals("application/json", $response->headers->get('Content-Type'));
        $this->assertJson($response->getContent());

        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->check_uuid_array($result, [1, 2]);
    }

    public function testAddMemberSuccess()
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
        $this->check_uuid_array($result, [18]);
    }

    public function testAddAdminSuccess()
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
        $this->check_uuid_array($result, [18]);


        // check if user is actually a member now
        $this->client->request('GET', '/api/clans/' . $uuid . "/users");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->check_uuid_array($result, [18]);
    }

    public function testRemoveAdminSuccess()
    {
        $uuid = Uuid::fromInteger(1002)->toString();
        $user = Uuid::fromInteger(3)->toString();

        $this->client->request('DELETE', '/api/clans/' . $uuid . "/admins/" . $user);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // check if user is an admin now
        $this->client->request('GET', '/api/clans/' . $uuid . "/admins");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(0, sizeof($result));

        // check if user is still a member
        $this->client->request('GET', '/api/clans/' . $uuid . "/users");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->check_uuid_array($result, [2,3,4]);
    }

    public function testRemoveAdminFailNotAdminButUser()
    {
        $uuid = Uuid::fromInteger(1002)->toString();
        $user = Uuid::fromInteger(2)->toString();

        $this->client->request('DELETE', '/api/clans/' . $uuid . "/admins/" . $user);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NOT_FOUND, $response->getStatusCode());

        // check if admins didn't change
        $this->client->request('GET', '/api/clans/' . $uuid . "/admins");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->check_uuid_array($result, [3]);

        // check if user is still a member
        $this->client->request('GET', '/api/clans/' . $uuid . "/users");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->check_uuid_array($result, [2,3,4]);
    }

    public function testRemoveAdminSuccessFromClan()
    {
        $uuid = Uuid::fromInteger(1002)->toString();
        $user = Uuid::fromInteger(3)->toString();

        $this->client->request('DELETE', '/api/clans/' . $uuid . "/users/" . $user);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // check if user is no admin any more
        $this->client->request('GET', '/api/clans/' . $uuid . "/admins");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->assertEquals(0, sizeof($result));

        // check if user is no member any more
        $this->client->request('GET', '/api/clans/' . $uuid . "/users");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->check_uuid_array($result, [2,4]);
    }

    public function testAddAdminSuccessPromoteUser()
    {
        $uuid = Uuid::fromInteger(1002)->toString();
        $user = Uuid::fromInteger(2)->toString();
        $data = <<<JSON
{
    "uuid": "{$user}"
}
JSON;

        $this->client->request('POST', '/api/clans/' . $uuid . "/admins", [], [], ['CONTENT_TYPE' => 'application/json'], $data);
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode());

        // check if user is admin now
        $this->client->request('GET', '/api/clans/' . $uuid . "/admins");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->check_uuid_array($result, [2,3]);

        // check if user is still member
        $this->client->request('GET', '/api/clans/' . $uuid . "/users");
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $result = json_decode($response->getContent(), true);
        $this->assertIsArray($result);
        $this->check_uuid_array($result, [2,3,4]);
    }

    /**
     * @param array[] $array array of arrays which contain one uuid value to search for
     * @param int[] $expected UUIDs to check for
     */
    private function check_uuid_array(array $array, array $expected)
    {
        $this->assertSameSize($array, $expected);
        $expected = array_map(function (int $i) { return Uuid::fromInteger($i)->toString();}, $expected);
        foreach ($array as $item) {
            $this->assertIsArray($item);
            $this->assertArrayHasKey("uuid", $item);
        }
        $array = array_map(function ($a) { return $a['uuid']; }, $array);
        foreach ($expected as $ex) {
            $this->assertContains($ex, $array);
        }
    }
}