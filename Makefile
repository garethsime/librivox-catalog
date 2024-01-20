TEST_SOURCEDIR := 'tests'
TEST_SOURCES := $(shell find $(TEST_SOURCEDIR) -name '*.php' ! -name 'autoload.php')

.PHONY: test
test: tests/autoload.php
	./phpunit --bootstrap tests/autoload.php tests

tests/autoload.php: $(TEST_SOURCES)
	./phpab -o tests/autoload.php -b tests tests/
