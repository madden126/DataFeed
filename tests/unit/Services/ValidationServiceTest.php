<?php

namespace Tests\Unit\Services;

use App\Services\ValidationService;
use Codeception\Test\Unit;
use RuntimeException;
use UnitTester;

class ValidationServiceTest extends Unit
{
    private ValidationService $service;

    protected function _before()
    {
        $this->service = new ValidationService();
    }

    public function testValidateDataReturnsSanitizedDataForValidInput(): void
    {
        $product = [
            'gtin' => '1234567890123',
            'language' => 'en',
            'title' => 'Sample Product',
            'picture' => 'https://example.com/image.jpg',
            'description' => 'A valid description.',
            'price' => '19.99',
            'stock' => '10'
        ];

        $result = $this->service->validateData($product);

        $this->assertEquals('1234567890123', $result['gtin']);
        $this->assertEquals('en', $result['language']);
        $this->assertEquals('Sample Product', $result['title']);
        $this->assertEquals('https://example.com/image.jpg', $result['picture']);
        $this->assertEquals('A valid description.', $result['description']);
        $this->assertEquals(19.99, $result['price']);
        $this->assertEquals(10, $result['stock']);
    }

    public function testValidateDataThrowsExceptionForMissingRequiredField(): void
    {
        $product = [
            'gtin' => '1234567890123',
            'language' => 'en',
            'title' => 'Sample Product',
            'picture' => 'https://example.com/image.jpg',
            'description' => 'A valid description.',
            'price' => '19.99'
            // Missing 'stock'
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing or empty required field: stock');

        $this->service->validateData($product);
    }

    public function testValidateDataThrowsExceptionForInvalidPrice(): void
    {
        $product = [
            'gtin' => '1234567890123',
            'language' => 'en',
            'title' => 'Sample Product',
            'picture' => 'https://example.com/image.jpg',
            'description' => 'A valid description.',
            'price' => '-5.00',
            'stock' => '10'
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid price value');

        $this->service->validateData($product);
    }

    public function testValidateDataThrowsExceptionForInvalidGtin(): void
    {
        $product = [
            'gtin' => '12345',
            'language' => 'en',
            'title' => 'Sample Product',
            'picture' => 'https://example.com/image.jpg',
            'description' => 'A valid description.',
            'price' => '19.99',
            'stock' => '10'
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid GTIN format');

        $this->service->validateData($product);
    }

    public function testValidateDataThrowsExceptionForInvalidUrl(): void
    {
        $product = [
            'gtin' => '1234567890123',
            'language' => 'en',
            'title' => 'Sample Product',
            'picture' => 'invalid-url',
            'description' => 'A valid description.',
            'price' => '19.99',
            'stock' => '10'
        ];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid URL format');

        $this->service->validateData($product);
    }
}
