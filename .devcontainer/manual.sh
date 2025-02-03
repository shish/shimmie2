#!/bin/sh
set -eu
docker run --rm -v $(pwd):/app -p 8000:8000 -ti $(docker build -q .)
