<?php

include("loklak.php");

class Testloklak extends \PHPUnit_Framework_TestCase
{
    private $loklak;

    public function setUp() {
        $this->loklak = new Loklak();
    }

    public function testStatus() {
        $result = $this->loklak->status();
        $statusResponse = json_decode($result);
        $statusResponse = $statusResponse->body;
        $statusResponse = json_decode($statusResponse, true);
        $this->assertArrayHasKey('index', $statusResponse);

        $resultProperties = array(
            'messages', 'mps', 'users', 'queries',
            'accounts', 'user', 'followers', 'following'
        );

        foreach($resultProperties as $property)
        {
            $error = "Indexes does not contain " . $property;
            $this->assertArrayHasKey($property, $statusResponse['index'], $error);
        }
    }

    public function testHello() {
        $result = $this->loklak->hello();
        $helloResponse = json_decode($result);
        $helloResponse = $helloResponse->body;
        $helloResponse = json_decode($helloResponse, true);
        $this->assertEquals('ok', $helloResponse['status']);
    }

    public function testGeocode() {
        $result = $this->loklak->geocode('Hyderabad');
        $geocodeResponse = json_decode($result);
        $geocodeResponse = $geocodeResponse->body;
        $geocodeResponse = json_decode($geocodeResponse, true);
        $this->assertArrayHasKey('locations', $geocodeResponse);
        $this->assertArrayHasKey('Hyderabad', $geocodeResponse['locations']);
        $this->assertEquals('IN', $geocodeResponse['locations']['Hyderabad']['country_code']);
        $this->assertInternalType('array', $geocodeResponse['locations']['Hyderabad']['place']);
    }

    public function testPeers() {
        $result = $this->loklak->peers();
        $peersResponse = json_decode($result);
        $peersResponse = $peersResponse->body;
        $peersResponse = json_decode($peersResponse, true);
        $this->assertArrayHasKey('peers', $peersResponse);
        $this->assertInternalType('array', $peersResponse['peers']);
        $this->assertTrue(sizeof($peersResponse['peers']) >= 1);
        $this->assertEquals(sizeof($peersResponse['peers']), $peersResponse['count']);
    }

    public function testUser() {
        $result = $this->loklak->user('Khoslasopan');
        $userResponse = json_decode($result);
        $userResponse = $userResponse->body;
        $userResponse = json_decode($userResponse, true);
        $this->assertArrayHasKey('user', $userResponse);
        $this->assertArrayHasKey('name', $userResponse['user']);
        $this->assertArrayHasKey('screen_name', $userResponse['user']);
    }

    public function testSearch() {
        $result = $this->loklak->search('doctor who');
        $searchResponse = json_decode($result);
        $searchResponse = $searchResponse->body;
        $searchResponse = json_decode($searchResponse, true);
        $this->assertArrayHasKey('statuses', $searchResponse);
        $this->assertInternalType('array', $searchResponse['statuses']);
        $this->assertTrue(sizeof($searchResponse['statuses']) >= 1);
        $this->assertEquals(sizeof($searchResponse['statuses']), $searchResponse['search_metadata']['count']);
    }

    public function testAggregations() {
        $result = $this->loklak->aggregations("spacex", "2016-04-01", "2016-04-06", array("mentions","hashtags"), 10, 6);
        $aggregationsResponse = json_decode($result);
        $aggregationsResponse = $aggregationsResponse->body;
        $aggregationsResponse = json_decode($aggregationsResponse, true);
        $this->assertArrayHasKey('statuses', $aggregationsResponse);
        $this->assertArrayHasKey('hashtags', $aggregationsResponse['aggregations']);
        $this->assertArrayHasKey('mentions', $aggregationsResponse['aggregations']);
    }
}
