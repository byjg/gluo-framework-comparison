<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $product = Product::factory()->create();

        $this->get(route('products.index'))->assertRedirect(route('login'));
        $this->post(route('products.store'), ['name' => 'Keyboard', 'price' => 10])->assertRedirect(route('login'));
        $this->put(route('products.update', $product), ['name' => 'Mouse', 'price' => 10])->assertRedirect(route('login'));
        $this->delete(route('products.destroy', $product))->assertRedirect(route('login'));

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => $product->name]);
        $this->assertNotSoftDeleted($product);
    }

    public function test_products_are_listed()
    {
        $this->actingAs(User::factory()->create());
        $products = Product::factory(3)->create();

        $this->get(route('products.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/index')
                ->has('products', 3)
                ->where('products.0.id', $products[2]->id)
                ->where('products.0.name', $products[2]->name)
            );
    }

    public function test_the_edit_form_receives_the_product()
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['name' => 'Keyboard', 'price' => 49.9]);

        $this->get(route('products.edit', $product))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('products/form')
                ->where('product.name', 'Keyboard')
                ->where('product.price', '49.90')
            );
    }

    public function test_a_product_can_be_created()
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('products.store'), ['name' => 'Keyboard', 'price' => 49.9])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['name' => 'Keyboard', 'price' => 49.90]);
    }

    public function test_a_product_is_validated()
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['name' => 'Keyboard']);

        $this->post(route('products.store'), ['name' => str_repeat('a', 101), 'price' => -1])
            ->assertSessionHasErrors(['name', 'price']);

        $this->put(route('products.update', $product), ['name' => '', 'price' => 'abc'])
            ->assertSessionHasErrors(['name', 'price']);

        $this->assertDatabaseCount('products', 1);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Keyboard']);
    }

    public function test_a_product_can_be_updated()
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['name' => 'Keyboard', 'price' => 49.9]);

        $this->put(route('products.update', $product), ['name' => 'Mouse', 'price' => 59])
            ->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Mouse', 'price' => 59]);
    }

    public function test_a_product_is_soft_deleted()
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create();

        $this->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        $this->assertSoftDeleted($product);
        $this->get(route('products.index'))
            ->assertInertia(fn (Assert $page) => $page->has('products', 0));
        $this->get(route('products.edit', $product))->assertNotFound();
    }
}
