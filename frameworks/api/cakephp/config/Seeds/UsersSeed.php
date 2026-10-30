<?php
declare(strict_types=1);

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Migrations\BaseSeed;

/**
 * Users seed.
 */
class UsersSeed extends BaseSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeds is available here:
     * https://book.cakephp.org/migrations/5/guides/seeding.html
     *
     * @return void
     */
    public function run(): void
    {
        // The same users as the other builds; a seed writes rows directly, so it hashes the password
        $password = (new DefaultPasswordHasher())->hash('!P4ssw0rdstr!');
        $now = date('Y-m-d H:i:s');
        $data = [
            ['email' => 'admin@example.com', 'password' => $password, 'name' => 'Administrator', 'role' => 'admin', 'created' => $now, 'modified' => $now],
            ['email' => 'user@example.com', 'password' => $password, 'name' => 'Regular User', 'role' => 'user', 'created' => $now, 'modified' => $now],
        ];

        $table = $this->table('users');
        $table->insert($data)->save();
    }
}
