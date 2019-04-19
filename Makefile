#!/usr/bin/make

# This will output the help for each task. thanks to https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
help: ## Show this help
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

init: ## Init project from scratch. Please run this task ONLY to start your project, not to update it!
	composer install
	php artisan key:generate
	php artisan migrate:fresh --seed
	npm i
	npm run prod

update: ## Update Pullkins source code
	composer install
	php artisan migrate --force
	npm run prod

