<?php

declare(strict_types=1);

namespace App\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    public static function initialize(): void
    {
        $capsule = new Capsule;

        $capsule->addConnection(require __DIR__ . '/../config/database.php');
        $capsule->setAsGlobal();
        $capsule->bootEloquent();
    }
}

Database::initialize();
