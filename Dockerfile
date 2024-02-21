ARG PHP_VERSION=8.2

# Tree of layers:
# base
# ├── dev-tools
# │   ├── build
# │   │   └── tests
# │   └── devcontainer
# └── run (copies built artifacts out of build)

# Install base packages
# Things which all stages (build, test, run) need
FROM debian:bookworm AS base
COPY --from=mwader/static-ffmpeg:6.1 /ffmpeg /ffprobe /usr/local/bin/
RUN apt update && \
    apt upgrade -y && \
    apt install -y curl && \
    curl --output /usr/share/keyrings/nginx-keyring.gpg https://unit.nginx.org/keys/nginx-keyring.gpg && \
    echo 'deb [signed-by=/usr/share/keyrings/nginx-keyring.gpg] https://packages.nginx.org/unit/debian/ bookworm unit' > /etc/apt/sources.list.d/unit.list && \
    apt update && apt install -y --no-install-recommends \
    php${PHP_VERSION}-cli \
    php${PHP_VERSION}-gd php${PHP_VERSION}-zip php${PHP_VERSION}-xml php${PHP_VERSION}-mbstring php${PHP_VERSION}-curl \
    php${PHP_VERSION}-pgsql php${PHP_VERSION}-mysql php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-memcached \
    curl imagemagick zip unzip unit unit-php && \
    rm -rf /var/lib/apt/lists/*

# Install dev packages
# Things which are only needed during development - Composer has 100MB of
# dependencies, so let's avoid including that in the final image
FROM base AS dev-tools
RUN apt update && apt upgrade -y && \
    apt install -y composer php${PHP_VERSION}-xdebug git procps net-tools vim && \
    rm -rf /var/lib/apt/lists/*
ENV XDEBUG_MODE=coverage

# "Build" shimmie (composer install)
# Done in its own stage so that we don't meed to include all the
# composer fluff in the final image
FROM dev-tools AS build
COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install --no-dev --no-progress
COPY . /app/

# Tests in their own image.
# Re-run composer install to get dev dependencies
FROM build AS tests
RUN composer install --no-progress
COPY . /app/
ARG RUN_TESTS=true
RUN [ $RUN_TESTS = false ] || (\
    echo '=== Installing ===' && mkdir -p data/config && INSTALL_DSN="sqlite:data/shimmie.sqlite" php index.php && \
    echo '=== Smoke Test ===' && php index.php get-page /post/list && \
    echo '=== Unit Tests ===' && ./vendor/bin/phpunit --configuration tests/phpunit.xml && \
    echo '=== Coverage ===' && ./vendor/bin/phpunit --configuration tests/phpunit.xml --coverage-text && \
    echo '=== Cleaning ===' && rm -rf data)

# Devcontainer target
# Contains all of the build and debug tools, but no code, since
# that's mounted from the host
FROM dev-tools AS devcontainer
EXPOSE 8000

# Actually run shimmie
FROM base AS run
EXPOSE 8000
# HEALTHCHECK --interval=1m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
ARG BUILD_TIME=unknown BUILD_HASH=unknown
ENV UID=1000 GID=1000
COPY --from=build /app /app
WORKDIR /app
RUN echo "_d('BUILD_TIME', '$BUILD_TIME');" >> core/sys_config.php && \
    echo "_d('BUILD_HASH', '$BUILD_HASH');" >> core/sys_config.php
ENTRYPOINT ["/app/.docker/entrypoint.sh"]
CMD ["php", "/app/.docker/run.php"]
