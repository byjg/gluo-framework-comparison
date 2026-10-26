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

    public static function invalidProductProvider(): array
    {
        return [
            'name longer than the column' => [['name' => str_repeat('a', 101), 'price' => '1']],
            'empty name' => [['name' => '', 'price' => '1']],
            'negative price' => [['name' => 'Mouse', 'price' => '-5']],
            'price above the column range' => [['name' => 'Mouse', 'price' => '100000000']],
            'missing name' => [['price' => '1']],
        ];
    }

    #[DataProvider('invalidProductProvider')]
    public function testAProductIsValidated(array $payload): void
    {
        $this->login();

        $this->post('/products/add', $payload);

        // The form is shown again, and nothing was stored.
        $this->assertNoRedirect();
        $this->assertResponseContains('could not be saved');
        $this->assertSame(1, $this->fetchTable('Products')->find()->count());
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
