<?php

namespace Tests\Integration\Console;

use App\Console\Parameters;
use App\Interfaces\Services\ValidationServiceInterface;
use App\Repository\Product\ProductRepository;
use App\Services\ValidationService;
use Codeception\Test\Unit;
use Mockery;
use ProcessCSV;
use ReflectionClass;
use RuntimeException;

require_once __DIR__ . '/../../../bin/process-csv.php';

class ProcessCSVTest extends Unit
{
    private string $testCsvFile;
    private ProductRepository $productRepository;
    private ValidationServiceInterface $validationService;

    protected function _before()
    {
        // Create a temporary CSV file for testing
        $this->testCsvFile = tempnam(sys_get_temp_dir(), 'test_csv_');
        $this->writeTestCsvFile();

        $this->productRepository = Mockery::mock(ProductRepository::class);
        $this->validationService = Mockery::mock(ValidationServiceInterface::class);
    }

    protected function _after()
    {
        // Clean up the temporary file
        if (file_exists($this->testCsvFile)) {
            unlink($this->testCsvFile);
        }
        Mockery::close();
    }

    public function testProcessValidCsvFile(): void
    {
        $this->validationService->shouldReceive('validateColumnNumber')
            ->times(3)
            ->andReturn(true);

        $validProduct = [
            'gtin' => '1234567890123',
            'language' => 'en',
            'title' => 'Test Product',
            'picture' => 'https://example.com/image.jpg',
            'description' => 'Test Description',
            'price' => 19.99,
            'stock' => 10
        ];

        $this->validationService->shouldReceive('validateData')
            ->times(3)
            ->andReturn($validProduct);

        $this->productRepository->shouldReceive('batchInsert')
            ->once()
            ->with(Mockery::on(function ($batch) {
                return count($batch) === 3;
            }), Mockery::any())
            ->andReturn(true);

        $parameters = new Parameters($this->testCsvFile);
        $processCSV = new ProcessCSV(
            $parameters,
            $this->productRepository,
            $this->validationService
        );
        $processCSV();

        $this->assertTrue(true);
    }

    public function testProcessCsvFileWithInvalidColumnCount(): void
    {
        $this->writeInvalidColumnCountCsvFile();
        $parameters = new Parameters($this->testCsvFile);

        $this->validationService->shouldReceive('validateColumnNumber')
            ->once()
            ->andReturn(false);

        $this->validationService->shouldReceive('validateData')->never();
        $this->productRepository->shouldReceive('batchInsert')->never();

        $processCSV = new ProcessCSV(
            $parameters,
            $this->productRepository,
            $this->validationService
        );
        $processCSV();

        $reflection = new ReflectionClass($processCSV);
        $errorsProperty = $reflection->getProperty('errors');
        $errorsProperty->setAccessible(true);
        $errors = $errorsProperty->getValue($processCSV);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Incorrect number of columns', $errors[0]);
    }

    public function testProcessCsvFileWithInvalidData(): void
    {
        $this->writeInvalidDataCsvFile();
        $parameters = new Parameters($this->testCsvFile);

        $this->validationService->shouldReceive('validateColumnNumber')
            ->once()
            ->andReturn(true);

        $this->validationService->shouldReceive('validateData')
            ->once()
            ->andThrow(new RuntimeException('Invalid GTIN format'));

        $this->productRepository->shouldReceive('batchInsert')->never();

        $processCSV = new ProcessCSV(
            $parameters,
            $this->productRepository,
            $this->validationService
        );
        $processCSV();

        $reflection = new ReflectionClass($processCSV);
        $errorsProperty = $reflection->getProperty('errors');
        $errorsProperty->setAccessible(true);
        $errors = $errorsProperty->getValue($processCSV);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('Invalid GTIN format', $errors[0]);
    }

    public function testProcessEmptyCsvFile(): void
    {
        $this->writeEmptyCsvFile();
        $parameters = new Parameters($this->testCsvFile);

        $this->validationService->shouldReceive('validateColumnNumber')->never();
        $this->validationService->shouldReceive('validateData')->never();
        $this->productRepository->shouldReceive('batchInsert')->never();

        $processCSV = new ProcessCSV(
            $parameters,
            $this->productRepository,
            $this->validationService
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Empty CSV file');
        $processCSV();
    }

    private function writeTestCsvFile(): void
    {
        $csvData = [
            ['gtin', 'language', 'title', 'picture', 'description', 'price', 'stock'],
            ['1234567890123', 'en', 'Test Product 1', 'https://example.com/image1.jpg', 'Test Description 1', '19.99', '10'],
            ['2345678901234', 'en', 'Test Product 2', 'https://example.com/image2.jpg', 'Test Description 2', '29.99', '20'],
            ['3456789012345', 'en', 'Test Product 3', 'https://example.com/image3.jpg', 'Test Description 3', '39.99', '30']
        ];

        $fp = fopen($this->testCsvFile, 'w');
        foreach ($csvData as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

    private function writeInvalidColumnCountCsvFile(): void
    {
        $csvData = [
            ['gtin', 'language', 'title', 'picture', 'description', 'price', 'stock'],
            ['1234567890123', 'en', 'Test Product'] // Missing columns
        ];

        $fp = fopen($this->testCsvFile, 'w');
        foreach ($csvData as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

    private function writeInvalidDataCsvFile(): void
    {
        $csvData = [
            ['gtin', 'language', 'title', 'picture', 'description', 'price', 'stock'],
            ['12345', 'en', 'Test Product', 'https://example.com/image.jpg', 'Test Description', '19.99', '10'] // Invalid GTIN
        ];

        $fp = fopen($this->testCsvFile, 'w');
        foreach ($csvData as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

    private function writeEmptyCsvFile(): void
    {
        $fp = fopen($this->testCsvFile, 'w');
        fclose($fp);
    }
}
