
PHPUNIT=php phpunit

.PHONY: goodDir

all: all_tests

all_tests: goodDir phpunit
	$(PHPUNIT)  FirstTest.php   # --bootstrap bootstrap.php
	$(PHPUNIT)  SecondTest.php    # --bootstrap bootstrap.php

phpunit:
	curl https://phar.phpunit.de/phpunit-11.5.46.phar > phpunit

phpunit.xml:
	@echo "You must be in tests directory to launch tests"
	@exit 1

goodDir: phpunit.xml