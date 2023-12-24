ARG PHP_VERSION=8.2

# Install base packages which all stages (build, test, run) need
FROM debian:bookworm AS base
COPY --from=mwader/static-ffmpeg:6.1 /ffmpeg /usr/local/bin/
RUN apt update && \
    apt upgrade -y && \
    apt install -y curl && \
    curl --output /usr/share/keyrings/nginx-keyring.gpg https://unit.nginx.org/keys/nginx-keyring.gpg && \
    echo 'deb [signed-by=/usr/share/keyrings/nginx-keyring.gpg] https://packages.nginx.org/unit/debian/ bookworm unit' > /etc/apt/sources.list.d/unit.list && \
    apt update && apt install -y --no-install-recommends \
    php${PHP_VERSION}-cli php${PHP_VERSION}-gd php${PHP_VERSION}-zip php${PHP_VERSION}-xml php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-pgsql php${PHP_VERSION}-mysql php${PHP_VERSION}-sqlite3 php${PHP_VERSION}-curl \
    curl imagemagick zip unzip unit unit-php gettext && \
    rm -rf /var/lib/apt/lists/*

# Composer has 100MB of dependencies, and we only need that during build and test
FROM base AS composer
RUN apt update && apt upgrade -y && \
    apt install -y composer php${PHP_VERSION}-xdebug git procps net-tools vim && \
    rm -rf /var/lib/apt/lists/*
ENV XDEBUG_MODE=coverage

# "Build" shimmie (composer install - done in its own stage so that we don't
# need to include all the composer fluff in the final image)
FROM composer AS build
COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install --no-dev
COPY . /app/

# Tests in their own image. Really we should inherit from app and then
# `composer install` phpunit on top of that; but for some reason
# `composer install --no-dev && composer install` doesn't install dev
FROM composer AS tests
COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install
COPY . /app/
ARG RUN_TESTS=true
RUN [ $RUN_TESTS = false ] || (\
    echo '=== Installing ===' && mkdir -p data/config && INSTALL_DSN="sqlite:data/shimmie.sqlite" php index.php && \
    echo '=== Smoke Test ===' && php index.php get-page /post/list && \
    echo '=== Unit Tests ===' && ./vendor/bin/phpunit --configuration tests/phpunit.xml && \
    echo '=== Coverage ===' && XDEBUG_MODE=coverage ./vendor/bin/phpunit --configuration tests/phpunit.xml --coverage-text && \
    echo '=== Cleaning ===' && rm -rf data)

# Devcontainer target
FROM composer AS devcontainer
EXPOSE 8000

# Actually run shimmie
FROM base AS run
EXPOSE 8000
# HEALTHCHECK --interval=1m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
ENV UID=1000 GID=1000 UPLOAD_MAX_FILESIZE=50M
COPY --from=build /app /app
WORKDIR /app
ENTRYPOINT ["/app/.docker/entrypoint.sh"]
CMD ["unitd", "--no-daemon", "--control", "unix:/var/run/control.unit.sock"]
