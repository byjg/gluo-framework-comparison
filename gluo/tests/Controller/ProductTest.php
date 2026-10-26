<?php

namespace Tests\Controller;

use ByJG\RestServer\Exception\Error400Exception;
use ByJG\AnyDataset\Db\DatabaseExecutor;
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
            ->withPath("/product/1")
            ->withRequestBody(json_encode($this->getSampleData(true)))
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
            ->withPath("/product/1")
            ->withRequestBody(json_encode($this->getSampleData(true)))
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
        $this->sendRequest($request);

        // The key is in the path; the body has the same fields as the POST
        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod('PUT')
            ->withPath("/product/" . $bodyAr['id'])
            ->withRequestBody(json_encode($this->getSampleData(true)))
            ->expectStatus(200)
            ->withRequestHeader([
                "Authorization" => "Bearer " . $result['token']
            ])
        ;
        $this->sendRequest($request);
    }

    /**
     * The server refuses an invalid body with 400, not only the test client: these requests
     * are sent without the OpenAPI schema, so the client does not stop them and they reach
     * #[ValidateRequest] in the controller.
     */
    public function testInvalidPayloadIsRejectedByTheServer()
    {
        $result = json_decode($this->sendRequest(Credentials::requestLogin(Credentials::getAdminUser()))->getBody()->getContents(), true);
        $sample = $this->getSampleData(true);
        $collection = "/product";
        $item = "/product/1";

        $invalid = [
            // The key is generated (POST) or taken from the path (PUT): the body cannot set it
            'POST with the key in the body' => ['POST', $collection, $sample + ['id' => 1]],
            'PUT with the key in the body' => ['PUT', $item, $sample + ['id' => 1]],
            'POST without the required fields' => ['POST', $collection, (object)[]],
            'PUT without the required fields' => ['PUT', $item, (object)[]],
            'POST with an empty name' => ['POST', $collection, array_merge($sample, ['name' => ''])],
            'PUT with an empty name' => ['PUT', $item, array_merge($sample, ['name' => ''])],
        ];

        foreach ($invalid as $case => [$method, $path, $payload]) {
            $request = new FakeApiRequester();
            $request
                ->withPsr7Request($this->getPsr7Request())
                ->withMethod($method)
                ->withPath($path)
                ->withRequestBody(json_encode($payload))
                ->withRequestHeader([
                    "Authorization" => "Bearer " . $result['token']
                ])
            ;

            try {
                $request->send();
                $this->fail("$case was accepted");
            } catch (Error400Exception) {
                $this->addToAssertionCount(1);
            }
        }
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
        $this->expectExceptionMessage('Product not found');
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
        $cases = [
            'name longer than the column' => [['name' => str_repeat('a', 101), 'price' => 1], "is longer than maxLength 100"],
            'empty name' => [['name' => '', 'price' => 1], "is shorter than minLength 1"],
            'negative price' => [['name' => 'Keyboard', 'price' => -5], "is less than minimum 0"],
            'price above the column range' => [['name' => 'Keyboard', 'price' => 100000000], "is greater than maximum 99999999.99"],
            'missing name' => [['price' => 1], "Required property 'name'"],
        ];

        $result = [];
        foreach (['POST', 'PUT'] as $method) {
            foreach ($cases as $name => [$payload, $message]) {
                $result["$method, $name"] = [$method, $payload, $message];
            }
        }
        return $result;
    }

    /**
     * Extends the generated testInvalidPayloadIsRejectedByTheServer, which covers the missing
     * fields and the empty name, to every limit of the spec: maxLength and minLength from the
     * column, written by codegen, and the price range added by hand, each on POST and PUT. The
     * request is sent without the spec, so the test client does not stop it: it reaches
     * #[ValidateRequest] in the controller, which answers 400, and nothing is written.
     */
    #[DataProvider('invalidProductProvider')]
    public function testInvalidProductIsRejectedByTheServer(string $method, array $payload, string $message)
    {
        $token = json_decode($this->sendRequest(Credentials::requestLogin(Credentials::getAdminUser()))->getBody()->getContents(), true)['token'];
        $executor = Config::get(DatabaseExecutor::class);
        $path = '/product';
        if ($method === 'PUT') {
            $path .= '/' . $executor->executeAndGetId("insert into product (name, price) values ('Keyboard', 49.9)");
        }
        $before = $executor->getIterator('select id, name, price from product order by id')->toArray();

        $request = new FakeApiRequester();
        $request
            ->withPsr7Request($this->getPsr7Request())
            ->withMethod($method)
            ->withPath($path)
            ->withRequestBody(json_encode($payload))
            ->withRequestHeader(['Authorization' => "Bearer $token"]);

        try {
            $request->send();
            $this->fail('The invalid product was accepted');
        } catch (Error400Exception $ex) {
            $this->assertStringContainsString($message, $ex->getMessage());
        }

        $this->assertSame($before, $executor->getIterator('select id, name, price from product order by id')->toArray());
    }

    /**
     * The model is checked against its own OpenAPI schema on every save, not only through the
     * API: a command or a queue consumer that saves a product is held to the same rules.
     */
    public function testAnInvalidProductIsRejectedWhenSavedOutsideTheApi()
    {
        $executor = Config::get(DatabaseExecutor::class);
        $count = $executor->getScalar('select count(*) from product');

        try {
            Product::new(['name' => 'Keyboard', 'price' => -5])->save();
            $this->fail('The negative price was saved');
        } catch (Error400Exception $ex) {
            $this->assertStringContainsString('is less than minimum 0', $ex->getMessage());
        }

        $this->assertEquals($count, $executor->getScalar('select count(*) from product'));
    }
}
