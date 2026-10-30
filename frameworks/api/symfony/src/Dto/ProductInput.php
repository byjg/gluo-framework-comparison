<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * The body of POST and PUT /product
 */
final class ProductInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name = '',

        #[Assert\NotNull]
        #[Assert\Range(min: 0, max: 99999999.99)]
        public ?float $price = null,
    ) {
    }
}
