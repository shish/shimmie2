#!/bin/sh
php \
	-d extension.dir=/usr/lib/php/extensions/no-debug-non-zts-20121212/ \
	-d extension=xdebug.so \
	-d xdebug.profiler_output_dir=./data/prof/ \
	-d xdebug.profiler_enable=1 \
	./phpunit.phar \
		--config tests/phpunit.xml \
		--coverage-clover data/coverage.clover
