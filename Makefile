DOCKER_COMPOSE                          = docker compose
DOCKER_COMPOSE_EXEC                     = $(DOCKER_COMPOSE) exec

.PHONY: build up down load-schema load-data deploy deploy-preprod deploy-production

# Existant docker services
#NGINX_SERVICE=nginx
PHP_SERVICE=convert
LATEX_SERVICE=latex

DOCKER_COMPOSE_EXEC_PHP 		= $(DOCKER_COMPOSE_EXEC) $(PHP_SERVICE)
DOCKER_COMPOSE_EXEC_LATEX 		= $(DOCKER_COMPOSE) run --rm -u root $(LATEX_SERVICE)
help:
	@echo "Usage:"
	@echo "  To install locally,         do : make build up"
	@echo "  To enter docker container,  do : make enter-php or make enter-latex..."
	@echo "  To test,                    do : make test  or TEST_PRGM=./tests/...Mytest.php make test"
	@echo "  To debug test,              do : make test-debug or  TEST_PRGM=./tests/...Mytest.php make test-debug"
#	@echo "           (Verify your docker IP and you must have a 'Docker' server configuration in your IDE)"
#	@echo "  To deploy                   do : make deploy-preprod or make deploy-production"

build:
	$(DOCKER_COMPOSE) --profile profile --profile cli-only build

up:
	$(DOCKER_COMPOSE) up -d --remove-orphans

down:
	$(DOCKER_COMPOSE) down

test:
	@$(DOCKER_COMPOSE_EXEC_PHP) bash -c "cd tests; make"

test-debug:
	@$(DOCKER_COMPOSE_EXEC)  -e XDEBUG_MODE=debug -e PHP_IDE_CONFIG="serverName=docker" $(PHP_SERVICE) /bin/bash -c "cd tests; make"

install: build up

enter-php:
	$(DOCKER_COMPOSE_EXEC_PHP) bash

enter-latex:
	$(DOCKER_COMPOSE_EXEC_LATEX) bash
