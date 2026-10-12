<?php

namespace App\Tests\Controller;

use App\Entity\Product;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();

        $this->manager->getConnection()->executeStatement('DELETE FROM product');
        $this->manager->getConnection()->executeStatement('DELETE FROM user');
    }

    private function login(): void
    {
        $user = (new User())->setEmail('email@example.com')->setPassword('not-used');
        $this->manager->persist($user);
        $this->manager->flush();

        $this->client->loginUser($user);
    }

    private function createProduct(string $name = 'Keyboard', string $price = '49.90'): Product
    {
        $product = (new Product())->setName($name)->setPrice($price);
        $this->manager->persist($product);
        $this->manager->flush();

        return $product;
    }

    private function countRows(string $where = '1 = 1'): int
    {
        return (int) $this->manager->getConnection()->fetchOne("SELECT COUNT(*) FROM product WHERE $where");
    }

    public function testGuestsAreRedirectedToLogin(): void
    {
        $product = $this->createProduct();

        foreach (['/product', '/product/new', "/product/{$product->getId()}", "/product/{$product->getId()}/edit"] as $path) {
            $this->client->request('GET', $path);
            self::assertResponseRedirects('/login');
        }

        $this->client->request('POST', "/product/{$product->getId()}");
        self::assertResponseRedirects('/login');
        self::assertSame(1, $this->countRows('deleted_at IS NULL'));
    }

    public function testIndex(): void
    {
        $this->login();
        $this->createProduct('Keyboard', '49.90');
        $this->createProduct('Mouse', '19.90');

        $crawler = $this->client->request('GET', '/product');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Product index');
        self::assertCount(2, $crawler->filter('tbody tr'));
        self::assertSelectorTextContains('tbody tr:first-child', 'Keyboard');
        self::assertSelectorTextContains('tbody tr:first-child', '49.90');
    }

    public function testNew(): void
    {
        $this->login();
        $this->client->request('GET', '/product/new');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Save', [
            'product[name]' => 'Keyboard',
            'product[price]' => '49.90',
        ]);

        self::assertResponseRedirects('/product');

        $product = $this->manager->getRepository(Product::class)->findOneBy(['name' => 'Keyboard']);
        self::assertSame('49.90', $product->getPrice());
        self::assertNotNull($product->getCreatedAt());
        self::assertNotNull($product->getUpdatedAt());
        self::assertNull($product->getDeletedAt());
    }

    public function testNewIsValidated(): void
    {
        $this->login();
        $this->client->request('GET', '/product/new');

        $this->client->submitForm('Save', [
            'product[name]' => str_repeat('a', 101),
            'product[price]' => '-1',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->countRows());
    }

    public function testShow(): void
    {
        $this->login();
        $product = $this->createProduct('Keyboard', '49.90');

        $this->client->request('GET', "/product/{$product->getId()}");

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Keyboard');
        self::assertSelectorTextContains('table', '49.90');
    }

    public function testEdit(): void
    {
        $this->login();
        $product = $this->createProduct('Keyboard', '49.90');

        $this->client->request('GET', "/product/{$product->getId()}/edit");
        $this->client->submitForm('Update', [
            'product[name]' => 'Mouse',
            'product[price]' => '59.00',
        ]);

        self::assertResponseRedirects('/product');

        self::assertSame(
            ['name' => 'Mouse', 'price' => '59.00'],
            $this->manager->getConnection()->fetchAssociative('SELECT name, price FROM product WHERE id = ?', [$product->getId()])
        );
    }

    public function testRemoveIsASoftDelete(): void
    {
        $this->login();
        $product = $this->createProduct();

        $this->client->request('GET', "/product/{$product->getId()}");
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/product');
        self::assertSame(1, $this->countRows('deleted_at IS NOT NULL'));

        $this->client->request('GET', "/product/{$product->getId()}");
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/product');
        self::assertSelectorTextContains('tbody', 'no records found');
    }
}
