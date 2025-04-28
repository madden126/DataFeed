<?php

declare(strict_types=1);

namespace App\Repository\Product;

use App\Interfaces\Repository\ProductRepositoryInterface;
use App\Models\Product;
use Illuminate\Database\Capsule\Manager as DB;
use RuntimeException;
use Throwable;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * @throws RuntimeException
     */
    public function batchInsert(array $batch, int $size): void
    {
        if (empty($batch)) {
            return;
        }

        try {
            DB::connection()->beginTransaction();

            $now = date('Y-m-d H:i:s');
            $records = array_map(static function ($product) use ($now) {
                return [
                    'gtin' => $product['gtin'],
                    'language' => $product['language'],
                    'title' => $product['title'],
                    'picture' => $product['picture'],
                    'description' => $product['description'],
                    'price' => (float)$product['price'],
                    'stock' => (int)$product['stock'],
                    'date_add' => $now,
                    'date_upd' => $now
                ];
            }, $batch);

            foreach (array_chunk($records, $size) as $chunk) {
                Product::query()->insert($chunk);
            }

            DB::connection()->commit();
        } catch (Throwable $e) {
            try {
                DB::connection()->rollBack();
            } catch (Throwable $rollbackException) {
                throw new RuntimeException("Rollback failed: " . $rollbackException->getMessage(), 0, $e);
            }
            throw new RuntimeException("Batch insert failed: " . $e->getMessage());
        }
    }

    public function insert(array $data): void
    {
        Product::query()->insert($data);
    }
}
