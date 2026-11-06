<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // The same users as the other builds; the password is hashed by the model (bcrypt)
        User::create(['name' => 'Administrator', 'email' => 'admin@example.com', 'password' => '!P4ssw0rdstr!', 'role' => 'admin']);
        User::create(['name' => 'Regular User', 'email' => 'user@example.com', 'password' => '!P4ssw0rdstr!', 'role' => 'user']);

        // createToken() issues its tokens through a personal access client
        app(ClientRepository::class)->createPersonalAccessGrantClient('spa', 'users');

        $this->call(ProductSeeder::class);
    }
}
