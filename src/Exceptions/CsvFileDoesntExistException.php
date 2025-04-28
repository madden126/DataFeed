<?php

namespace App\Exceptions;

use RuntimeException;

class CsvFileDoesntExistException extends RuntimeException
{
    public function __construct( string $fileName = '')
    {
        parent::__construct(sprintf('The CSV file does not exist: %s', $fileName));
    }

}
