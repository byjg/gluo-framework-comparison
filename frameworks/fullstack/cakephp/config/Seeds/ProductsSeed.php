<?php
declare(strict_types=1);

use Migrations\BaseSeed;

/**
 * Products seed.
 */
class ProductsSeed extends BaseSeed
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
        $data = [];
        foreach (['Keyboard' => 49.90, 'Mouse' => 19.90, 'Monitor' => 899.00, 'Headset' => 129.50, 'Webcam' => 79.00] as $name => $price) {
            $data[] = ['name' => $name, 'price' => $price, 'created_at' => $now, 'updated_at' => $now];
        }

        $table = $this->table('products');
        $table->insert($data)->save();
    }
}
