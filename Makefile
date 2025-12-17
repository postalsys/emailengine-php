# EmailEngine PHP SDK - Makefile
#
# Common development and testing commands

.PHONY: help install test test-unit test-coverage lint lint-fix phpstan \
        docker-build docker-test docker-test-all docker-integration docker-clean \
        docker-up docker-down docker-logs

# Default target
help:
	@echo "EmailEngine PHP SDK - Development Commands"
	@echo ""
	@echo "Local Development:"
	@echo "  make install        Install dependencies"
	@echo "  make test           Run unit tests"
	@echo "  make test-coverage  Run tests with coverage report"
	@echo "  make lint           Check code style (PSR-12)"
	@echo "  make lint-fix       Fix code style issues"
	@echo "  make phpstan        Run static analysis"
	@echo ""
	@echo "Docker Testing:"
	@echo "  make docker-build   Build test Docker images"
	@echo "  make docker-test    Run unit tests in Docker (PHP 8.3)"
	@echo "  make docker-test-all Run tests on all PHP versions (8.1-8.4)"
	@echo "  make docker-integration Run integration tests with EmailEngine"
	@echo ""
	@echo "Docker Services:"
	@echo "  make docker-up      Start EmailEngine + Redis"
	@echo "  make docker-down    Stop all Docker services"
	@echo "  make docker-logs    Show EmailEngine logs"
	@echo "  make docker-clean   Remove all Docker resources"

# =============================================================================
# Local Development
# =============================================================================

install:
	COMPOSER_NO_DEV=0 composer install

test:
	./vendor/bin/phpunit --testsuite unit

test-unit: test

test-coverage:
	./vendor/bin/phpunit --testsuite unit --coverage-html coverage

lint:
	./vendor/bin/phpcs --standard=PSR12 src

lint-fix:
	./vendor/bin/phpcbf --standard=PSR12 src

phpstan:
	./vendor/bin/phpstan analyse src --level=8

# =============================================================================
# Docker Testing
# =============================================================================

docker-build:
	docker compose build

docker-test:
	docker compose run --rm test

docker-test-php81:
	docker compose run --rm test-php81

docker-test-php82:
	docker compose run --rm test-php82

docker-test-php83:
	docker compose run --rm test-php83

docker-test-php84:
	docker compose run --rm test-php84

docker-test-all: docker-build
	@echo "=== Testing PHP 8.1 ==="
	docker compose run --rm test-php81
	@echo ""
	@echo "=== Testing PHP 8.2 ==="
	docker compose run --rm test-php82
	@echo ""
	@echo "=== Testing PHP 8.3 ==="
	docker compose run --rm test-php83
	@echo ""
	@echo "=== Testing PHP 8.4 ==="
	docker compose run --rm test-php84
	@echo ""
	@echo "=== All PHP versions passed ==="

docker-integration:
	@echo "Starting EmailEngine and Redis..."
	docker compose up -d redis emailengine
	@echo "Waiting for services to be healthy..."
	@sleep 5
	docker compose run --rm integration
	@echo "Stopping services..."
	docker compose down

# =============================================================================
# Docker Services Management
# =============================================================================

docker-up:
	docker compose up -d redis emailengine
	@echo ""
	@echo "EmailEngine is starting..."
	@echo "  - API: http://localhost:3000"
	@echo "  - Web UI: http://localhost:3000"
	@echo "  - SMTP: localhost:2525"
	@echo "  - IMAP Proxy: localhost:9993"
	@echo ""
	@echo "Note: Without a license, workers suspend after 15 minutes."
	@echo "Use 'make docker-logs' to view logs."

docker-down:
	docker compose down

docker-logs:
	docker compose logs -f emailengine

docker-clean:
	docker compose down -v --rmi local
	rm -rf .phpunit.cache coverage

# =============================================================================
# CI/CD Helpers
# =============================================================================

ci-test: docker-test-all docker-integration
	@echo "All CI tests passed!"
