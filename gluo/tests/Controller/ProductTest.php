<?php

namespace Tests\Controller;

use ByJG\RestServer\Exception\Error400Exception;
use ByJG\AnyDataset\Db\DatabaseExecutor;
use ByJG\ApiTools\Exception\NotMatchedException;
use ByJG\ApiTools\Exception\RequiredArgumentNotFound;
use ByJG\Config\Config;
use ByJG\RestServer\Exception\Error404Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use ByJG\RestServer\Exception\Error401Exception;
use ByJG\RestServer\Exception\Error403Exception;
use ByJG\Serializer\ObjectCopy;
use App\Model\Product;
use ByJG\Gluo\Repository\BaseRepository;
use ByJG\Gluo\Util\FakeApiRequester;

class ProductTest extends BaseApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @return Product|array
     */
    protected function getSampleData($array = false)
    {
        $sample = [

            'name' => 'name',
            'price' => 1.1,
        ];

        if ($array) {
            return $sample;
        }

        ObjectCopy::copy($sample, $model = new Product());
        return $model;
    }



    public function testGetUnauthorized()
    {
        $this->expectException(Error401Exception::class);
        $this->expectExceptionMessage('Absent authorization token');

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('GET')
            ->withPath("/product/1")
            ->expectStatus(401)
        ;
        $this->sendRequest($request);
    }

    public function testListUnauthorized()
    {
        $this->expectException(Error401Exception::class);
        $this->expectExceptionMessage('Absent authorization token');

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('GET')
            ->withPath("/product/1")
            ->expectStatus(401)
        ;
        $this->sendRequest($request);
    }

    public function testPostUnauthorized()
    {
        $this->expectException(Error401Exception::class);
        $this->expectExceptionMessage('Absent authorization token');

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('POST')
            ->withPath("/product")
            ->withRequestBody(json_encode($this->getSampleData(true)))
            ->expectStatus(401)
        ;
        $this->sendRequest($request);
    }

    public function testPutUnauthorized()
    {
        $this->expectException(Error401Exception::class);
        $this->expectExceptionMessage('Absent authorization token');

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('PUT')
            ->withPath("/product")
            ->withRequestBody(json_encode($this->getSampleData(true) + ['id' => 1]))
            ->expectStatus(401)
        ;
        $this->sendRequest($request);
    }

    public function testPostInsufficientPrivileges()
    {
        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');

        $result = json_decode($this->sendRequest(Credentials::requestLogin(Credentials::getRegularUser()))->getBody()->getContents(), true);

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('POST')
            ->withPath("/product")
            ->withRequestBody(json_encode($this->getSampleData(true)))
            ->expectStatus(403)
            ->withRequestHeader([
                "Authorization" => "Bearer " . $result['token']
            ])
        ;
        $this->sendRequest($request);
    }

    public function testPutInsufficientPrivileges()
    {
        $this->expectException(Error403Exception::class);
        $this->expectExceptionMessage('Insufficient privileges');

        $result = json_decode($this->sendRequest(Credentials::requestLogin(Credentials::getRegularUser()))->getBody()->getContents(), true);

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('PUT')
            ->withPath("/product")
            ->withRequestBody(json_encode($this->getSampleData(true) + ['id' => 1]))
            ->expectStatus(403)
            ->withRequestHeader([
                "Authorization" => "Bearer " . $result['token']
            ])
        ;
        $this->sendRequest($request);
    }

    public function testFullCrud()
    {
        $result = json_decode($this->sendRequest(Credentials::requestLogin(Credentials::getAdminUser()))->getBody()->getContents(), true);

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('POST')
            ->withPath("/product")
            ->withRequestBody(json_encode($this->getSampleData(true)))
            ->expectStatus(200)
            ->withRequestHeader([
                "Authorization" => "Bearer " . $result['token']
            ])
        ;
        $body = $this->sendRequest($request);
        $bodyAr = json_decode($body->getBody()->getContents(), true);

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('GET')
            ->withPath("/product/" . $bodyAr['id'])
            ->expectStatus(200)
            ->withRequestHeader([
                "Authorization" => "Bearer " . $result['token']
            ])
        ;
        $body = $this->sendRequest($request);

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('PUT')
            ->withPath("/product")
            ->withRequestBody($body->getBody()->getContents())
            ->expectStatus(200)
            ->withRequestHeader([
                "Authorization" => "Bearer " . $result['token']
            ])
        ;
        $this->sendRequest($request);
    }

    public function testList()
    {
        $result = json_decode($this->sendRequest(Credentials::requestLogin(Credentials::getRegularUser()))->getBody()->getContents(), true);

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('GET')
            ->withPath("/product")
            ->expectStatus(200)
            ->withRequestHeader([
                "Authorization" => "Bearer " . $result['token']
            ])
        ;
        $this->sendRequest($request);
    }

    public function testListOrderBy()
    {
        $result = json_decode($this->sendRequest(Credentials::requestLogin(Credentials::getRegularUser()))->getBody()->getContents(), true);

        $list = function (string $orderBy) use ($result) {
            $request = new FakeApiRequester();
            $request
                ->withPsr7Request($this->getPsr7Request())
                ->withMethod('GET')
                ->withPath("/product")
                ->withQuery(['orderBy' => $orderBy])
                ->expectStatus(200)
                ->withRequestHeader([
                    "Authorization" => "Bearer " . $result['token']
                ])
            ;
            return json_decode($this->sendRequest($request)->getBody()->getContents(), true);
        };

        $descending = array_column($list('id desc'), 'id');
        $expected = $descending;
        rsort($expected);
        $this->assertSame($expected, $descending);

        // A field the model does not have is refused, not passed to the database.
        $this->expectException(Error400Exception::class);
        $this->expectExceptionMessage("unknown field 'notAField'");
        $list('notAField');
    }

    public function testSoftDelete()
    {
        $token = json_decode($this->sendRequest(Credentials::requestLogin(Credentials::getAdminUser()))->getBody()->getContents(), true)['token'];

        $created = json_decode($this->sendRequest(
            (new FakeApiRequester())
                ->withPsr7Request($this->getPsr7Request())
                ->withMethod('POST')
                ->withPath('/product')
                ->withRequestBody(json_encode($this->getSampleData(true)))
                ->withRequestHeader(['Authorization' => "Bearer $token"])
                ->expectStatus(200)
        )->getBody()->getContents(), true);
        $id = $created['id'];

        $this->sendRequest(
            (new FakeApiRequester())
                ->withPsr7Request($this->getPsr7Request())
                ->withMethod('DELETE')
                ->withPath("/product/$id")
                ->withRequestHeader(['Authorization' => "Bearer $token"])
                ->expectStatus(200)
        );

        // The row stays in the table with deleted_at set...
        $executor = Config::get(DatabaseExecutor::class);
        $this->assertNotNull($executor->getScalar('select deleted_at from product where id = :id', ['id' => $id]));

        // ...but it is hidden from the API.
        $this->expectException(Error404Exception::class);
        $this->sendRequest(
            (new FakeApiRequester())
                ->withPsr7Request($this->getPsr7Request())
                ->withMethod('GET')
                ->withPath("/product/$id")
                ->withRequestHeader(['Authorization' => "Bearer $token"])
                ->expectStatus(404)
        );
    }

    public static function invalidProductProvider(): array
    {
        return [
            'name longer than the column' => [['name' => str_repeat('a', 101), 'price' => 1], "is longer than maxLength 100"],
            'negative price' => [['name' => 'Keyboard', 'price' => -5], "is less than minimum 0"],
            'price above the column range' => [['name' => 'Keyboard', 'price' => 100000000], "is greater than maximum 99999999.99"],
            'missing name' => [['price' => 1], "Required property 'name'"],
        ];
    }

    /**
     * The limits come from the OpenAPI spec: maxLength from the column, written by codegen,
     * and the price range added by hand. The contract check refuses the payload before it is stored.
     */
    #[DataProvider('invalidProductProvider')]
    public function testPostInvalidProductIsRejected(array $payload, string $message)
    {
        $token = json_decode($this->sendRequest(Credentials::requestLogin(Credentials::getAdminUser()))->getBody()->getContents(), true)['token'];
        $executor = Config::get(DatabaseExecutor::class);
        $before = $executor->getScalar('select count(*) from product');

        try {
            $this->sendRequest(
                (new FakeApiRequester())
                    ->withPsr7Request($this->getPsr7Request())
                    ->withMethod('POST')
                    ->withPath('/product')
                    ->withRequestBody(json_encode($payload))
                    ->withRequestHeader(['Authorization' => "Bearer $token"])
                    ->expectStatus(200)
            );
            $this->fail('The invalid product was accepted');
        } catch (NotMatchedException | RequiredArgumentNotFound $ex) {
            $this->assertStringContainsString($message, $ex->getMessage());
        }

        $this->assertSame($before, $executor->getScalar('select count(*) from product'));
    }
}
