<?php

namespace App\Database\Seeds;

use App\Models\ProductModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // The same users as the other builds, through Shield's user provider
        $users = auth()->getProvider();
        foreach ([['Administrator', 'admin@example.com', 'admin'], ['Regular User', 'user@example.com', 'user']] as [$name, $email, $group]) {
            $users->save(new User(['username' => $name, 'email' => $email, 'password' => '!P4ssw0rdstr!']));
            $users->findById($users->getInsertID())->addGroup($group);
        }

        $products = model(ProductModel::class);
        foreach ([['Keyboard', 49.90], ['Mouse', 19.90], ['Monitor', 899.00], ['Headset', 129.50], ['Webcam', 75.00]] as [$name, $price]) {
            $products->insert(['name' => $name, 'price' => $price]);
        }
    }
}
