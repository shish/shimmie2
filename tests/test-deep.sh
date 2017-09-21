#!/bin/sh
php \
	-d xdebug.profiler_output_dir=./data/prof/ \
	-d xdebug.profiler_enable=1 \
	./vendor/bin/phpunit \
		--config tests/phpunit.xml \
		--coverage-clover data/coverage.clover
#	-d extension.dir=/usr/local/Cellar/php71-xdebug/2.5.5/ \
#	-d extension=xdebug.so \
