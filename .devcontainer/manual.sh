#!/bin/sh
set -eu
# build in verbose mode to populate the cache
docker build .
# build in quiet mode to get just the ID, should
# be very fast because we cached
docker run --rm -v $(pwd):/app -p 8000:8000 -t $(docker build -q .)
