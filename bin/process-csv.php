<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

use App\Console\Parameters;
use App\Exceptions\CsvFileDoesntExistException;
use App\Exceptions\MissingCsvFileException;
use App\Interfaces\Services\ValidationServiceInterface;
use App\Repository\Product\ProductRepository;
use App\Services\ValidationService;

class ProcessCSV
{
    private int $errorCount = 0;
    private array $errors = [];
    private int $batchSize = 1000;

    private int $rowCount = 0;
    private int $rowProcessed = 0;

    private array $batch = [];
    private int $batchCount = 0;
    private int $batchProcessed = 0;

    public function __construct(
        private readonly Parameters $parameters,
        private readonly ProductRepository $productRepository,
        private readonly ValidationServiceInterface $validationService
    ) {
    }

    public function __invoke(): void
    {
        $this->readCsvFile();
    }

    private function readCsvFile(): void
    {
        // Set reasonable limits for large file processing
        ini_set('max_execution_time', 3600); // 1 hour
        ini_set('memory_limit', '1G');

        $startTime = microtime(true);
        $handle = fopen($this->parameters->csvFile, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Could not open CSV file: {$this->parameters->csvFile}");
        }

        $headers = fgetcsv($handle, escape: '\\');
        if ($headers === false) {
            throw new RuntimeException("Empty CSV file");
        }

        $expectedColumnCount = count($headers);
        $this->batchCount = 0;
        while (($data = fgetcsv($handle, escape: '\\')) !== false) {
            $this->rowCount++;

            if ($this->parameters->row !== 0) {
                $this->processCSVRow($data, $expectedColumnCount, $headers);
                if ($this->rowProcessed > 0) {
                    break;
                }
            } elseif ($this->parameters->batch !== 0) {
                $this->processCSVBatch($data, $expectedColumnCount, $headers);
                if ($this->batchProcessed > 0) {
                    break;
                }
            } else {
                $this->processCSV($data, $expectedColumnCount, $headers);
            }
        }

        // Process any remaining records
        if (!empty($this->batch)) {
            try {
                $this->productRepository->batchInsert($this->batch, $this->batchSize);
                $this->batchProcessed++;
                $this->rowProcessed += count($this->batch);
            } catch (Exception $e) {
                $this->errorCount++;
                $this->errors[] = "Error processing remaining records: " . $e->getMessage();
            }
        }

        fclose($handle);

        $timeElapsed = round(microtime(true) - $startTime, 2);
        echo "\nProcessing completed:\n";
        echo "Total rows processed: {$this->rowProcessed}\n";
        echo "Total batch processed: {$this->batchProcessed}\n";
        echo "Total errors encountered: {$this->errorCount}\n";
        if ($this->errorCount > 0) {
            echo "Errors:\n";
            foreach ($this->errors as $error) {
                echo "- {$error}\n";
            }
        }
        echo "Time elapsed: {$timeElapsed} seconds\n";
    }

    /**
     * @throws RuntimeException
     */
    private function validateRow(false|array $data, int $expectedColumnCount, array $headers): array
    {
        if (!$this->validationService->validateColumnNumber($data, $expectedColumnCount)) {
            throw new RuntimeException("Incorrect number of columns");
        }

        $row = array_combine($headers, $data);
        try {
            $dataRow = $this->validationService->validateData($row);
        } catch (RuntimeException $e) {
            throw new RuntimeException("Validation error: " . $e->getMessage());
        }

        return $dataRow;
    }


    /**
     * @throws RuntimeException
     */
    private function processCSVRow(array $data, int $expectedColumnCount, array $headers): void
    {
        if ($this->rowCount !== $this->parameters->row) {
            return;
        }

        try {
            $dataRow = $this->validateRow($data, $expectedColumnCount, $headers);

            try {
                $this->productRepository->insert($dataRow);
            } catch (Exception $e) {
                throw new RuntimeException("Row insert failed: " . $e->getMessage());
            }
        } catch (Exception $e) {
            $this->errorCount++;
            $this->errors[] = "Error in row {$this->rowCount}: " . $e->getMessage();
        }
        $this->rowProcessed++;
    }


    /**
     * @throws RuntimeException
     */
    private function processCSVBatch(array $data, int $expectedColumnCount, array $headers): void
    {
        $currentBatch = (int)floor(($this->rowCount - 1) / $this->batchSize);

        if ($currentBatch !== $this->parameters->batch) {
            return;
        }

        try {
            $dataRow = $this->validateRow($data, $expectedColumnCount, $headers);

            $this->batch[] = $dataRow;
            if (count($this->batch) >= $this->batchSize) {
                $this->productRepository->batchInsert($this->batch, $this->batchSize);
                $this->batch = [];
                $this->batchProcessed++;
                $this->rowProcessed += $this->batchSize;
            }
        } catch (Exception $e) {
            $this->errorCount++;
            $this->errors[] = "Error in batch {$currentBatch}: " . $e->getMessage();
        }
    }

    /**
     * @throws RuntimeException
     */
    private function processCSV(array $data, int $expectedColumnCount, array $headers): void
    {
        try {
            $dataRow = $this->validateRow($data, $expectedColumnCount, $headers);

            $this->batch[] = $dataRow;
            // Batch processing
            if (count($this->batch) >= $this->batchSize) {
                $this->batchCount++;
                try {
                    $this->productRepository->batchInsert($this->batch, $this->batchSize);
                } catch (Exception $e) {
                    throw new RuntimeException("Batch insert failed: " . $e->getMessage());
                }
                $this->batch = [];

                // Simple progress indicator every 10 rows
                if ($this->rowCount % 10000 === 0) {
                    echo "\rProcessed {$this->rowCount} rows...";
                    gc_collect_cycles();
                }
                $this->batchProcessed++;
                $this->rowProcessed += $this->batchSize;
            }
        } catch (Exception $e) {
            $this->errorCount++;
            $this->errors[] = "Error in batch {$this->batchCount}: " . $e->getMessage();
            $this->batch = [];
        }
    }
}

// Only execute this code when the script is run directly, not when included example unit tests
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    try {
        $parameters = Parameters::validateParameters($argc, $argv);
    } catch (MissingCsvFileException|CsvFileDoesntExistException $e) {
        echo $e->getMessage() . PHP_EOL;
        exit();
    }

    $processCSV = new ProcessCSV($parameters, new ProductRepository(), new ValidationService());
    $processCSV();
}

