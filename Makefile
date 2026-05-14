.PHONY: help install test cache-clear console php-bash

CONTAINER := nginx_fpm_shared
APP_DIR := /srv/www/nginx/sites/newfactory_web

help: ## Show this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  %-20s %s\n", $$1, $$2}' $(MAKEFILE_LIST)

install: ## composer install inside container
	docker exec -e COMPOSER_AUTH="$$COMPOSER_AUTH" $(CONTAINER) sh -c "cd $(APP_DIR) && composer install --no-interaction"

test: ## Run PHPUnit
	docker exec $(CONTAINER) sh -c "cd $(APP_DIR) && APP_ENV=test php vendor/bin/phpunit"

cache-clear: ## Clear cache
	docker exec $(CONTAINER) sh -c "cd $(APP_DIR) && APP_ENV=dev php bin/console cache:clear"

console: ## Run console command (CMD=...)
	docker exec $(CONTAINER) sh -c "cd $(APP_DIR) && APP_ENV=dev php bin/console $(CMD)"

php-bash: ## Shell into container
	docker exec -it $(CONTAINER) sh -c "cd $(APP_DIR) && exec bash"
