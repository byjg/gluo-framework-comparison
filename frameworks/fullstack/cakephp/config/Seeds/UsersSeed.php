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
        $now = date('Y-m-d H:i:s');
        $data = [[
            'email' => 'admin@example.com',
            // A seed writes rows directly, so the password is hashed here, not by the entity.
            'password' => (new DefaultPasswordHasher())->hash('!P4ssw0rdstr!'),
            'created' => $now,
            'modified' => $now,
        ]];

        $table = $this->table('users');
        $table->insert($data)->save();
    }
}
