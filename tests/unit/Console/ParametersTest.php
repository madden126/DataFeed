<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use App\Console\Parameters;
use App\Exceptions\CsvFileDoesntExistException;
use App\Exceptions\MissingCsvFileException;
use Codeception\Test\Unit;
use InvalidArgumentException;

class ParametersTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * Test that parameters are correctly parsed with no options
     */
    public function testParseParametersWithNoOptions(): void
    {
        $argc = 2;
        $argv = [
            'bin/process-csv.php',
            'src/feed.csv'
        ];

        $parameters = Parameters::validateParameters($argc, $argv);

        $this->assertEquals('src/feed.csv', $parameters->csvFile);
        $this->assertEquals(0, $parameters->row);
        $this->assertEquals(0, $parameters->batch);
    }

    /**
     * Test that parameters are correctly parsed with batch option
     */
    public function testParseParametersWithBatchOption(): void
    {
        $argc = 4;
        $argv = [
            'bin/process-csv.php',
            'src/feed.csv',
            '-b',
            '2'
        ];

        $parameters = Parameters::validateParameters($argc, $argv);

        $this->assertEquals('src/feed.csv', $parameters->csvFile);
        $this->assertEquals(0, $parameters->row);
        $this->assertEquals(2, $parameters->batch);
    }

    /**
     * Test that parameters are correctly parsed with row option
     */
    public function testParseParametersWithRowOption(): void
    {
        $argc = 4;
        $argv = [
            'bin/process-csv.php',
            'src/feed.csv',
            '-r',
            '5'
        ];

        $parameters = Parameters::validateParameters($argc, $argv);

        $this->assertEquals('src/feed.csv', $parameters->csvFile);
        $this->assertEquals(5, $parameters->row);
        $this->assertEquals(0, $parameters->batch);
    }

    /**
     * Test that an exception is thrown when CSV file is missing
     */
    public function testMissingCsvFileException(): void
    {
        $this->expectException(MissingCsvFileException::class);

        $argc = 1;
        $argv = [
            'bin/process-csv.php'
        ];

        Parameters::validateParameters($argc, $argv);
    }

    /**
     * Test that an exception is thrown when CSV file doesn't exist
     */
    public function testCsvFileDoesntExistException(): void
    {
        $this->expectException(CsvFileDoesntExistException::class);

        $argc = 2;
        $argv = [
            'bin/process-csv.php',
            'nonexistent.csv'
        ];

        Parameters::validateParameters($argc, $argv);
    }

    /**
     * Test that an exception is thrown when row is not numeric
     */
    public function testRowNotNumericException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Row number must be a number');

        $argc = 4;
        $argv = [
            'bin/process-csv.php',
            'src/feed.csv',
            '-r',
            'abc'
        ];

        Parameters::validateParameters($argc, $argv);
    }

    /**
     * Test that an exception is thrown when batch is not numeric
     */
    public function testBatchNotNumericException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Batch number must be a number');

        $argc = 4;
        $argv = [
            'bin/process-csv.php',
            'src/feed.csv',
            '-b',
            'abc'
        ];

        Parameters::validateParameters($argc, $argv);
    }

    /**
     * Test that an exception is thrown when both row and batch are specified
     */
    public function testBothRowAndBatchException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('You can only process one row or batch at a time');

        $argc = 6;
        $argv = [
            'bin/process-csv.php',
            'src/feed.csv',
            '-r',
            '5',
            '-b',
            '2'
        ];

        Parameters::validateParameters($argc, $argv);
    }
} 