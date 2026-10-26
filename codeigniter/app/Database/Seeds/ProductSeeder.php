<?php

namespace App\Database\Seeds;

use App\Models\ProductModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $users = auth()->getProvider();
        $users->save(new User([
            'username' => 'admin',
            'email'    => 'admin@example.com',
            'password' => '!P4ssw0rdstr!',
        ]));
        $users->findById($users->getInsertID())->addGroup('admin');

        $products = model(ProductModel::class);
        foreach (['Keyboard' => 49.90, 'Mouse' => 19.90, 'Monitor' => 899.00, 'Headset' => 129.50, 'Webcam' => 79.00] as $name => $price) {
            $products->insert(['name' => $name, 'price' => $price]);
        }
    }
}
