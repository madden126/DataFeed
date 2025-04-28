<?php

declare(strict_types=1);

namespace App\Interfaces\Services;

use RuntimeException;

interface ValidationServiceInterface
{

    /**
     * @throws RuntimeException
     */
    public function validateData(array $product): array;

    public function validateColumnNumber(array $data, int $expectedColumnCount): bool;


}
