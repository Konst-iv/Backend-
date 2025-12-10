<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Override;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthControllerTest extends WebTestCase
{
    private $client;
    private $entityManager;
    private $passwordHasher;

    #[Override]
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
    }

    public function testRegisterSuccess(): void
    {
        $uniquePhone = '+7912' . rand(1000000, 9999999);

        $userData = [
            'email' => 'test_register@example.com',
            'phone' => $uniquePhone,
            'name' => 'Test User',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(201);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('test_register@example.com', $data['data']['user']['email']);
        $this->assertEquals($uniquePhone, $data['data']['user']['phone']);
        $this->assertEquals('Test User', $data['data']['user']['name']);
        $this->assertArrayHasKey('token', $data['data']);
        $this->assertNotEmpty($data['data']['token']);
    }

    public function testRegisterWithMissingFields(): void
    {
        $userData = [
            'email' => 'test@example.com',
            'phone' => '+79123456789'
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Missing required field', $data['error']);
    }

    public function testRegisterWithDuplicateEmail(): void
    {
        $user = new User();
        $user->setEmail('duplicate@example.com');
        $user->setPhone('+79123456701');
        $user->setName('First User');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userData = [
            'email' => 'duplicate@example.com',
            'phone' => '+79123456702',
            'name' => 'Second User',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
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

    public function testRegisterWithDuplicatePhone(): void
    {
        $user = new User();
        $user->setEmail('test1@example.com');
        $user->setPhone('+79123456703');
        $user->setName('First User');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $userData = [
            'email' => 'test2@example.com',
            'phone' => '+79123456703',
            'name' => 'Second User',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/auth/register',
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

    public function testLoginSuccess(): void
    {
        $user = new User();
        $user->setEmail('test_login@example.com');
        $user->setPhone('+79123456704');
        $user->setName('Login Test User');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $loginData = [
            'phone' => '+79123456704',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('test_login@example.com', $data['data']['user']['email']);
        $this->assertEquals('Login Test User', $data['data']['user']['name']);
        $this->assertArrayHasKey('token', $data['data']);
        $this->assertNotEmpty($data['data']['token']);
    }

    public function testLoginWithInvalidPhone(): void
    {
        $loginData = [
            'phone' => '+79123456799',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid credentials', $data['error']);
    }

    public function testLoginWithInvalidPassword(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPhone('+79123456705');
        $user->setName('Test User');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'correctpassword'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $loginData = [
            'phone' => '+79123456705',
            'password' => 'wrongpassword'
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Invalid credentials', $data['error']);
    }

    public function testLoginWithMissingFields(): void
    {
        $loginData = [
            'phone' => '+79123456789'
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $this->assertResponseStatusCodeSame(400);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Missing required fields', $data['error']);
    }

    public function testMeWithoutToken(): void
    {
        $this->client->request('GET', '/api/auth/me');

        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Full authentication is required to access this resource.', $data['error']);
    }

    public function testMeWithValidToken(): void
    {
        $user = new User();
        $user->setEmail('test_me@example.com');
        $user->setPhone('+79123456706');
        $user->setName('Me Test User');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'password123'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $loginData = [
            'phone' => '+79123456706',
            'password' => 'password123'
        ];

        $this->client->request(
            'POST',
            '/api/auth/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($loginData)
        );

        $loginResponse = json_decode($this->client->getResponse()->getContent(), true);
        $token = $loginResponse['data']['token'];

        $this->client->request(
            'GET',
            '/api/auth/me',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer ' . $token]
        );

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('test_me@example.com', $data['data']['email']);
        $this->assertEquals('+79123456706', $data['data']['phone']);
        $this->assertEquals('Me Test User', $data['data']['name']);
        $this->assertArrayHasKey('roles', $data['data']);
        $this->assertContains('ROLE_USER', $data['data']['roles']);
    }

    public function testMeWithInvalidToken(): void
    {
        $this->client->request(
            'GET',
            '/api/auth/me',
            [],
            [],
            ['HTTP_AUTHORIZATION' => 'Bearer invalid_token_here']
        );

        $this->assertResponseStatusCodeSame(401);

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertFalse($data['success']);
        $this->assertStringContainsString('Authentication failed', $data['error']);
    }

    public function testLogout(): void
    {
        $this->client->request('POST', '/api/auth/logout');

        $this->assertResponseIsSuccessful();

        $response = $this->client->getResponse();
        $data = json_decode($response->getContent(), true);

        $this->assertTrue($data['success']);
        $this->assertEquals('Successfully logged out', $data['message']);
    }

    #[Override]
    protected function tearDown(): void
    {
        parent::tearDown();

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM users');

        $this->entityManager->close();
        $this->entityManager = null;
    }
}
