<?php

declare(strict_types=1);

namespace App\Console;

use App\Exceptions\CsvFileDoesntExistException;
use App\Exceptions\MissingCsvFileException;
use InvalidArgumentException;


readonly class Parameters
{
    public function __construct(
        public string $csvFile = '',
        public ?int $row = 0,
        public ?int $batch = 0
    ) {
    }

    /**
     * @throws MissingCsvFileException|CsvFileDoesntExistException
     */
    public static function validateParameters(int $argc, array $argv): Parameters
    {
        // Parse command line options
        $options = [];
        for ($i = 1; $i < $argc; $i++) {
            if ($argv[$i] === '-h') {
                self::showHelp();
                exit(0);
            }

            if ($argv[$i] === '-b' && isset($argv[$i + 1])) {
                $options['b'] = $argv[$i + 1];
                $i++;
            } elseif ($argv[$i] === '-r' && isset($argv[$i + 1])) {
                $options['r'] = $argv[$i + 1];
                $i++;
            }
        }

        if ($argc === 1) {
            throw new MissingCsvFileException();
        }

        if ($argv[1] && !file_exists($argv[1])) {
            throw new CsvFileDoesntExistException($argv[1]);
        }

        $row = $options['r'] ?? 0;
        if (isset($options['r']) && !is_numeric($row)) {
            throw new InvalidArgumentException('Row number must be a number');
        }

        $batch = $options['b'] ?? 0;
        if (isset($options['b']) && !is_numeric($batch)) {
            throw new InvalidArgumentException('Batch number must be a number');
        }

        if ($row > 0 && $batch > 0) {
            throw new InvalidArgumentException('You can only process one row or batch at a time');
        }

        return new Parameters($argv[1], (int)$row, (int)$batch);
    }

    /**
     * Display help information
     */
    private static function showHelp(): void
    {
        echo "Usage: php process-csv.php <csv_file> [-b batch_number] [-r row_number]\n";
        echo "Options:\n";
        echo "  -b    Process specific batch number\n";
        echo "  -r    Process specific row number\n";
        echo "  -h    Show this help message\n";
    }
}
