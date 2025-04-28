<?php

namespace App\Exceptions;

use RuntimeException;

class MissingCsvFileException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(sprintf('Missing the CSV file as argument'));
    }

}
