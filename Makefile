.PHONY: all setup import import-big clean view-data count-data test-unit test-integration process-batch process-row

# Default target
all: setup import

# Setup everything in one go
setup:
	mkdir -p docker/mariadb/data
	cp .env.example .env
	docker-compose down -v
	docker-compose up -d
	docker-compose exec app composer install
	@echo "Waiting for database to be ready..."
	@sleep 5
	docker-compose exec app vendor/bin/doctrine-migrations migrate --no-interaction


# Import regular CSV file
import:
	docker-compose exec -T app php bin/process-csv.php src/feed.csv

# Process a specific batch
process-batch:
	@if [ -z "$(BATCH)" ]; then \
		echo "Error: BATCH parameter is required. Usage: make process-batch BATCH=2"; \
		exit 1; \
	fi
	docker-compose exec -T app php bin/process-csv.php src/feedBig.csv -b $(BATCH)

# Process a specific row
process-row:
	@if [ -z "$(ROW)" ]; then \
		echo "Error: ROW parameter is required. Usage: make process-row ROW=5"; \
		exit 1; \
	fi
	docker-compose exec -T app php bin/process-csv.php src/feedBig.csv -r $(ROW)

# View the first 10 records from the database
view-data:
	@echo "Showing first 10 records from products table:"
	@docker-compose exec db mysql -u user -psecret myapp -e "SELECT * FROM products LIMIT 10;"

# Count total records in the database
count-data:
	@echo "Total number of records in products table:"
	@docker-compose exec db mysql -u user -psecret myapp -e "SELECT COUNT(*) as total_records FROM products;"

# Clean up everything
clean:
	docker-compose down -v
	rm -rf docker/mariadb/data/*

analyse-phpstan:
	@docker-compose exec -T app composer phpstan
	@echo "PHPStan analysis completed."

test-unit:
	@docker-compose exec app vendor/bin/codecept run unit
	@echo "Unit tests completed."

test-integration:
	@docker-compose exec app vendor/bin/codecept run integration
	@echo "Integration tests completed."
