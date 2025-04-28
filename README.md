# DataFeed CSV Processor

A PHP application to process product data from CSV files and import them into a MySQL database. The application is designed to handle large CSV files efficiently using batch processing.

## Requirements

- PHP 8.4
- Docker and Docker Compose
- Composer for PHP dependencies

## Quick Start with Make

The project includes a Makefile for simplified operation:

## CSV Files in project
- feed.csv (100 entries)
- feedBig.csv (500,000 entries)


```bash
# First time setup (creates directories, starts containers)
make setup

# Import the regular CSV file
make import

# Process a specific batch -> batch size is set to 1000.
# This is using the feedBig.csv. Useful for large files and to rerun failed batches
make process-batch BATCH=2

# Process a specific row
# This is using the feedBig.csv. Useful for large files and to rerun failed rows
make process-row ROW=5

# View the first 10 records from the database
make view-data

# See total number of records in the database
make count-data

# Run unit tests
make test-unit

# Run integration tests
make test-integration

# do a codebase analyzse with PHPStan
make analyse-phpstan

# Clean up everything (stops containers, removes data)
make clean
```

## Manual Setup

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd DataFeed
   ```

2. **Create required directories:**
   ```bash
   mkdir -p docker/mariadb/data
   ```

3. **Environment Setup:**
   ```bash
   cp .env.example .env
   ```
   Adjust the `.env` file with your preferred settings:
   ```env
   DB_HOST=db
   DB_PORT=3306
   DB_DATABASE=myapp
   DB_USERNAME=user
   DB_PASSWORD=secret
   ```

4. **Start Docker Services:**
   ```bash
   docker-compose up --build -d
   ```

5. **Install Dependencies:**
   ```bash
   docker-compose exec app composer install
   ```

6. **Run Migrations:**
   ```bash
   docker-compose exec app vendor/bin/doctrine-migrations migrate --no-interaction
   ```

## Docker Configuration

The application uses Docker for consistent development and deployment environments:

### Services

1. **Database (db)**:
   - MariaDB 10.11
   - Exposed port: 3306
   - Persistent volume: `docker/mariadb/data`
   - Environment variables configurable via `.env`
   - Important MariaDB Configuration:
     ```
     - character-set-server: utf8mb4
     - collation-server: utf8mb4_unicode_ci
     - innodb-large-prefix: 1
     - innodb-file-format: Barracuda
     - innodb-file-per-table: 1
     - innodb-use-native-aio: 0
     ```
   These settings ensure:
   - Proper UTF-8 support for international characters
   - Optimal InnoDB performance
   - Support for large text fields
   - Per-table file storage for better management

2. **Application (app)**:
   - PHP 8.1
   - Composer pre-installed
   - Working directory: `/var/www`
   - Mounts:
      - Source code
      - Composer cache
      - PHP configuration


### Current Schema

The migrations will create the following table:
```sql
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gtin VARCHAR(13) NOT NULL,
    language VARCHAR(2) NOT NULL,
    title VARCHAR(255) NOT NULL,
    picture VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL,
    date_add DATETIME NOT NULL,
    date_upd DATETIME NOT NULL,
    INDEX idx_gtin (gtin)
);
```

## Usage

Process a CSV file using the command:
files in project:
- feed.csv (100 entries)
- feedBig.csv (500,000 entries)

```bash
# Process entire file
docker-compose exec app php bin/process-csv.php src/feed.csv

# Process a specific batch -> batch size is set to 1000. So for better results use the feedBig.csv
docker-compose exec app php bin/process-csv.php src/feed.csv -b 2

# Process a specific row
docker-compose exec app php bin/process-csv.php src/feed.csv -r 5

# Show help
docker-compose exec app php bin/process-csv.php -h
```

### CSV Format
The CSV file should have the following columns:
- gtin (13 digits)
- language
- title
- picture (URL)
- description
- price (numeric)
- stock (numeric)

Example:
```csv
gtin,language,title,picture,description,price,stock
7034621736823,it,Product 1,http://example.com/image1.jpg,Description 1,738.70,99
```

### Features

- Batch processing for efficient memory usage
- Transaction support for data integrity
- Progress reporting during import
- Error logging and reporting
- Data validation for each record
- Ability to process specific batches or rows

### Error Handling

The processor validates:
- Required fields presence
- GTIN format (13 digits)
- Price validity (numeric and positive)
- Stock validity (numeric and non-negative)

Errors are reported in the console output with specific row numbers and error descriptions.

## Performance

The application is optimized for large CSV files:
- Processes records in batches of 1000
- Uses database transactions
- Implements garbage collection
- Shows progress every 10,000 rows
- Memory limit set to 1GB
- Maximum execution time set to 1 hour
