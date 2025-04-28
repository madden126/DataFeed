#!/usr/bin/env php
<?php declare(strict_types=1);

// doctrine.php (Doctrine Migrations CLI Bootstrap)

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\YamlFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Doctrine\Migrations\Tools\Console\Command;

// 1. Load DBAL Connection
/** @var Connection $connection */
$connection = require __DIR__ . '/config/database.php';
if (!$connection instanceof Connection) {
    echo "Error: config/database.php must return a valid Doctrine\DBAL\Connection object.\n";
    exit(1);
}

// 2. Load Migrations Configuration
$configLoader = new YamlFile(__DIR__ . '/migrations.yml');

// 3. Create Dependency Factory
$dependencyFactory = DependencyFactory::fromConnection(
    $configLoader,
    new ExistingConnection($connection)
);

// 4. Get Commands from Dependency Factory
$commands = [
    new Command\DumpSchemaCommand($dependencyFactory),
    new Command\ExecuteCommand($dependencyFactory),
    new Command\GenerateCommand($dependencyFactory),
    new Command\LatestCommand($dependencyFactory),
    new Command\ListCommand($dependencyFactory),
    new Command\MigrateCommand($dependencyFactory),
    new Command\RollupCommand($dependencyFactory),
    new Command\StatusCommand($dependencyFactory),
    new Command\SyncMetadataCommand($dependencyFactory),
    new Command\VersionCommand($dependencyFactory),
    new Command\UpToDateCommand($dependencyFactory),
    // Add DiffCommand if ORM is setup or handle potential errors
    // new Command\DiffCommand($dependencyFactory),
];

// 5. Run the CLI with the commands
ConsoleRunner::run($commands, $dependencyFactory); 