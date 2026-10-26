<?php

namespace Tests\Feature;

use App\Models\ProductModel;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Test\AuthenticationTesting;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\Fabricator;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\FeatureTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class ProductTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;
    use AuthenticationTesting;

    // Run every migration (the application's and Shield's) on the test database.
    protected $namespace = null;

    protected User $user;
    protected ProductModel $products;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = (new Fabricator(UserModel::class))->create();
        $this->products = model(ProductModel::class);
    }

    /**
     * POST as the logged-in user, with the CSRF token a real form sends: the csrf filter is
     * on for every POST.
     */
    protected function submit(string $uri, array $data = [])
    {
        return $this->actingAs($this->user)->post($uri, $data + [csrf_token() => csrf_hash()]);
    }

    public function testAPostWithoutTheCsrfTokenIsRefused(): void
    {
        $this->expectException(SecurityException::class);
        $this->actingAs($this->user)->post('products/create', ['name' => 'Keyboard', 'price' => '49.90']);
    }

    public function testGuestsAreRedirectedToTheLoginPage(): void
    {
        auth()->logout();

        $this->get('products')->assertRedirectTo(site_url('login'));
    }

    public function testProductsAreListed(): void
    {
        $this->products->insert(['name' => 'Keyboard', 'price' => 49.90]);

        $result = $this->actingAs($this->user)->get('products');

        $result->assertOK();
        $result->assertSee('Keyboard');
    }

    public function testTheEditFormReceivesTheProduct(): void
    {
        $id = $this->products->insert(['name' => 'Keyboard', 'price' => 49.90]);

        $result = $this->actingAs($this->user)->get("products/edit/$id");

        $result->assertOK();
        $result->assertSee('value="Keyboard"');
    }

    public function testAProductCanBeCreated(): void
    {
        $result = $this->submit('products/create', ['name' => 'Keyboard', 'price' => '49.90']);

        $result->assertRedirectTo(site_url('products'));
        $this->seeInDatabase('products', ['name' => 'Keyboard', 'price' => '49.90']);
    }

    public static function invalidProductProvider(): array
    {
        return [
            'name longer than the column' => [['name' => str_repeat('a', 101), 'price' => '1']],
            'empty name' => [['name' => '', 'price' => '1']],
            'negative price' => [['name' => 'Keyboard', 'price' => '-5']],
            'price above the column range' => [['name' => 'Keyboard', 'price' => '100000000']],
            'missing name' => [['price' => '1']],
        ];
    }

    #[DataProvider('invalidProductProvider')]
    public function testAProductIsValidated(array $payload): void
    {
        $result = $this->submit('products/create', $payload);

        $result->assertSessionHas('errors');
        $this->assertSame(0, $this->products->countAllResults());
    }

    public function testAProductCanBeUpdated(): void
    {
        $id = $this->products->insert(['name' => 'Keyboard', 'price' => 49.90]);

        $result = $this->submit("products/update/$id", ['name' => 'Mechanical keyboard', 'price' => '89.90']);

        $result->assertRedirectTo(site_url('products'));
        $this->seeInDatabase('products', ['id' => $id, 'name' => 'Mechanical keyboard', 'price' => '89.90']);
    }

    public function testAProductIsSoftDeleted(): void
    {
        $id = $this->products->insert(['name' => 'Keyboard', 'price' => 49.90]);

        $this->submit("products/delete/$id")->assertRedirectTo(site_url('products'));

        // The row is still there, with deleted_at set...
        $this->assertNotNull($this->products->withDeleted()->find($id)['deleted_at']);
        // ...and it is hidden from the list.
        $this->actingAs($this->user)->get('products')->assertDontSee('Keyboard');
    }
}
