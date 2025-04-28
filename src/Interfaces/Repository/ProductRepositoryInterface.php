<?php

declare(strict_types=1);

namespace App\Interfaces\Repository;

interface ProductRepositoryInterface
{
    public function batchInsert(array $batch, int $size): void;

    public function insert(array $data): void;

}
