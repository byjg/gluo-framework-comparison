<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ProductsFixture
 */
class ProductsFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'name' => 'Keyboard',
                'price' => 49.9,
                'created_at' => '2026-10-26 00:42:03',
                'updated_at' => '2026-10-26 00:42:03',
                'deleted_at' => null,
            ],
        ];
        parent::init();
    }
}
