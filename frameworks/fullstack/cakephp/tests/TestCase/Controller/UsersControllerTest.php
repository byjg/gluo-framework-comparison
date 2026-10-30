<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class UsersControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Users',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Saved through the entity, so the password is hashed as in production.
        $users = $this->fetchTable('Users');
        $users->saveOrFail($users->newEntity(['email' => 'admin@example.com', 'password' => '!P4ssw0rdstr!']));
        $this->enableCsrfToken();
    }

    public function testTheRightPasswordLogsIn(): void
    {
        $this->post('/users/login', ['email' => 'admin@example.com', 'password' => '!P4ssw0rdstr!']);

        $this->assertRedirect('/products');
        $this->assertSession('admin@example.com', 'Auth.email');
    }

    public function testAWrongPasswordIsRefused(): void
    {
        $this->post('/users/login', ['email' => 'admin@example.com', 'password' => 'wrong']);

        $this->assertNoRedirect();
        $this->assertResponseContains('Invalid e-mail or password.');
        $this->assertSessionNotHasKey('Auth');
    }
}
