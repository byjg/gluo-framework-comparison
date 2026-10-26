<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ProductsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.Products',
        'app.Users',
    ];

    /**
     * Act as the fixture user, and send the CSRF token a real form sends: the CSRF middleware
     * checks every POST.
     */
    protected function login(): void
    {
        $this->session(['Auth' => $this->fetchTable('Users')->get(1)]);
        $this->enableCsrfToken();
    }

    public function testGuestsAreRedirectedToTheLoginPage(): void
    {
        $this->get('/products');

        $this->assertRedirectContains('/users/login');
    }

    public function testAPostWithoutTheCsrfTokenIsRefused(): void
    {
        $this->session(['Auth' => $this->fetchTable('Users')->get(1)]);

        $this->post('/products/add', ['name' => 'Mouse', 'price' => '19.90']);

        $this->assertResponseCode(403);
    }

    public function testProductsAreListed(): void
    {
        $this->login();

        $this->get('/products');

        $this->assertResponseOk();
        $this->assertResponseContains('Keyboard');
    }

    public function testTheEditFormReceivesTheProduct(): void
    {
        $this->login();

        $this->get('/products/edit/1');

        $this->assertResponseOk();
        $this->assertResponseContains('value="Keyboard"');
    }

    public function testAProductCanBeCreated(): void
    {
        $this->login();

        $this->post('/products/add', ['name' => 'Mouse', 'price' => '19.90']);

        $this->assertRedirect(['controller' => 'Products', 'action' => 'index']);
        $this->assertSame(1, $this->fetchTable('Products')->find()->where(['name' => 'Mouse', 'price' => 19.90])->count());
    }

    /** Each invalid payload, sent to create a product and to update the existing one. */
    public static function invalidProductProvider(): array
    {
        $payloads = [
            'name longer than the column' => ['name' => str_repeat('a', 101), 'price' => '1'],
            'empty name' => ['name' => '', 'price' => '1'],
            'negative price' => ['name' => 'Mouse', 'price' => '-5'],
            'price above the column range' => ['name' => 'Mouse', 'price' => '100000000'],
            'missing name' => ['price' => '1'],
        ];

        $cases = [];
        foreach (['add' => '/products/add', 'edit' => '/products/edit/1'] as $action => $url) {
            foreach ($payloads as $name => $payload) {
                $cases["$action, $name"] = [$url, $payload];
            }
        }

        return $cases;
    }

    #[DataProvider('invalidProductProvider')]
    public function testAProductIsValidated(string $url, array $payload): void
    {
        $this->login();

        $this->post($url, $payload);

        // The form is shown again, and nothing was stored or changed.
        $this->assertNoRedirect();
        $this->assertResponseContains('could not be saved');
        $products = $this->fetchTable('Products');
        $this->assertSame(1, $products->find()->count());
        $this->assertSame('Keyboard', $products->get(1)->name);
    }

    public function testAProductCanBeUpdated(): void
    {
        $this->login();

        $this->post('/products/edit/1', ['name' => 'Mechanical keyboard', 'price' => '89.90']);

        $this->assertRedirect(['controller' => 'Products', 'action' => 'index']);
        $product = $this->fetchTable('Products')->get(1);
        $this->assertSame('Mechanical keyboard', $product->name);
        $this->assertEquals(89.90, (float)$product->price);
    }

    public function testAProductIsSoftDeleted(): void
    {
        $this->login();

        $this->post('/products/delete/1');

        $this->assertRedirect(['controller' => 'Products', 'action' => 'index']);
        $products = $this->fetchTable('Products');
        // The row is still there, with deleted_at set...
        $this->assertNotNull($products->find('withTrashed')->where(['id' => 1])->firstOrFail()->deleted_at);
        // ...and it is hidden from the queries.
        $this->assertSame(0, $products->find()->count());
    }
}
