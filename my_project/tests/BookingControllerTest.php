<?php

declare(strict_types=1);

namespace App\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookingControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testGetAvailableHouses(): void
    {
        $this->client->request('GET', '/api/houses/available');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('data', $data);
        $this->assertIsArray($data['data']);

        $this->assertGreaterThan(0, count($data['data']));

        $house = $data['data'][0];
        $this->assertArrayHasKey('id', $house);
        $this->assertArrayHasKey('name', $house);
        $this->assertArrayHasKey('beds', $house);
        $this->assertArrayHasKey('amenities', $house);
        $this->assertArrayHasKey('distanceToSea', $house);
        $this->assertArrayHasKey('pricePerNight', $house);
        $this->assertArrayHasKey('isAvailable', $house);
        $this->assertTrue($house['isAvailable']);
    }

    public function testCreateUser(): void
    {
        $userData = [
            'email' => 'test_api@example.com',
            'phone' => '+79123456780',
            'name' => 'API Test User'
        ];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(201);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('test_api@example.com', $data['data']['email']);
        $this->assertEquals('API Test User', $data['data']['name']);
        $this->assertArrayHasKey('id', $data['data']);
    }

    public function testCreateUserWithDuplicateEmail(): void
    {
        $userData = [
            'email' => 'duplicate@example.com',
            'phone' => '+79123456781',
            'name' => 'First User'
        ];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );
        $this->assertResponseStatusCodeSame(201);

        $userData = [
            'email' => 'duplicate@example.com',
            'phone' => '+79123456782',
            'name' => 'Second User'
        ];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('already exists', $data['error']);
    }

    public function testCreateBooking(): void
    {
        $userData = [
            'email' => 'booking_test@example.com',
            'phone' => '+79123456783',
            'name' => 'Booking Test User'
        ];

        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $userResponse = json_decode($this->client->getResponse()->getContent(), true);
        $userId = $userResponse['data']['id'];

        $bookingData = [
            'userId' => $userId,
            'houseId' => 1,
            'comment' => 'API Test booking',
            'checkIn' => '2024-01-20',
            'checkOut' => '2024-01-25'
        ];

        $this->client->request(
            'POST',
            '/api/bookings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $this->assertResponseStatusCodeSame(201);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('API Test booking', $data['data']['comment']);
        $this->assertEquals('confirmed', $data['data']['status']);
        $this->assertArrayHasKey('user', $data['data']);
        $this->assertArrayHasKey('house', $data['data']);
    }

    public function testCreateBookingWithInvalidData(): void
    {
        $bookingData = [
            'houseId' => 1,
            'comment' => 'Test booking'
        ];

        $this->client->request(
            'POST',
            '/api/bookings',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($bookingData)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Missing required field', $data['error']);
    }

    #[\Override]
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
