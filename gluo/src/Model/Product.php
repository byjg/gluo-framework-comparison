<?php

namespace App\Model;

use ByJG\MicroOrm\Attributes\FieldAttribute;
use ByJG\MicroOrm\Attributes\FieldUuidAttribute;
use ByJG\MicroOrm\Attributes\TableAttribute;
use ByJG\MicroOrm\Attributes\TableMySqlUuidPKAttribute;
use ByJG\MicroOrm\Literal\LiteralInterface;
use ByJG\MicroOrm\Literal\HexUuidLiteral;
use ByJG\MicroOrm\Query;
use ByJG\MicroOrm\Trait\ActiveRecord;
use RuntimeException;
use OpenApi\Attributes as OA;
use ByJG\Gluo\Trait\OaCreatedAt;
use ByJG\Gluo\Trait\OaUpdatedAt;
use ByJG\Gluo\Trait\OaDeletedAt;


/**
 * Class Product
 * @package App\Model
 */
#[OA\Schema(required: ["id", "name", "price"], type: "object", xml: new OA\Xml(name: "Product"))]
#[TableAttribute("product")]
class Product
{
    // Add the ActiveRecord trait to enable Active Record pattern
    use ActiveRecord;

    // Add timestamp traits for automatic timestamp handling
    use OaCreatedAt;
    use OaUpdatedAt;
    use OaDeletedAt;


    /**
     * @var int|null
     */
    #[OA\Property(type: "integer", format: "int32")]
    #[FieldAttribute(primaryKey: true, fieldName: "id")]
    protected int|null $id = null;

    /**
     * @var string|null
     */
    #[OA\Property(type: "string", format: "string", maxLength: 100)]
    #[FieldAttribute(fieldName: "name")]
    protected string|null $name = null;

    /**
     * @var float|null
     */
    #[OA\Property(type: "number", format: "double", minimum: 0, maximum: 99999999.99)]
    #[FieldAttribute(fieldName: "price")]
    protected float|null $price = null;



    /**
     * @return int|null
     */
    public function getId(): int|null
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return $this
     */
    public function setId(int|null $id): static
    {
        
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): string|null
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(string|null $name): static
    {
        
        $this->name = $name;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getPrice(): float|null
    {
        return $this->price;
    }

    /**
     * @param float|null $price
     * @return $this
     */
    public function setPrice(float|null $price): static
    {
        
        $this->price = $price;
        return $this;
    }


}
