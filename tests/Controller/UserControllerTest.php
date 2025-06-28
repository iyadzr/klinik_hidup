<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    public function testProfileImageUploadRequiresAuth()
    {
        $client = static::createClient();
        $client->request('POST', '/api/users/profile-image');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetProfileRequiresAuth()
    {
        $client = static::createClient();
        $client->request('GET', '/api/users/1');
        $this->assertResponseStatusCodeSame(401);
    }

    // Add more tests for successful upload and profile fetch with authentication if needed
}
