# Tree of layers:
# base
# ├── dev-tools
# │   ├── build
# │   └── devcontainer
# └── run (copies built artifacts out of build)

# Install base packages
# Things which all stages (build, test, run) need
FROM php:8.3-cli-bookworm AS base

# copy individual files from unit:php rather than inheriting
# `FROM unit:php` because we don't want to inherit EXPOSE settings
COPY --from=unit:php8.3 /var/lib/unit /var/lib/unit/
COPY --from=unit:php8.3 /usr/lib/unit /usr/lib/unit/
COPY --from=unit:php8.3 /usr/sbin/unitd /usr/sbin/unitd
RUN true \
    && groupadd --gid 999 unit \
    && useradd \
         --uid 999 \
         --gid unit \
         --no-create-home \
         --home /nonexistent \
         --comment "unit user" \
         --shell /bin/false \
         unit \
    && ln -sf /dev/stderr /var/log/unit.log

COPY --from=mwader/static-ffmpeg:7.1 /ffmpeg /ffprobe /usr/local/bin/

RUN apt update && \
    apt upgrade -y && \
    apt install -y --no-install-recommends \
    curl rsync imagemagick zip unzip \
    libpq-dev \
    libzip-dev \
    libpng-dev libjpeg-dev libwebp-dev libavif-dev \
    libmemcached-dev libssl-dev zlib1g-dev && \
    rm -rf /var/lib/apt/lists/*
RUN pecl install redis-6.1.0 && docker-php-ext-enable redis
RUN pecl install apcu-5.1.24 && docker-php-ext-enable apcu
RUN pecl install memcached-3.3.0 && docker-php-ext-enable memcached
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-avif && \
    docker-php-ext-install gd
RUN docker-php-ext-install mysqli pgsql pdo pdo_mysql pdo_pgsql zip pcntl

# Install dev packages
# Things which are only needed during development - Composer has 100MB of
# dependencies, so let's avoid including that in the final image
FROM base AS dev-tools
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN apt update && apt upgrade -y && \
    apt install -y git procps net-tools vim && \
    rm -rf /var/lib/apt/lists/*
RUN pecl install xdebug-3.4.0 && docker-php-ext-enable xdebug

# "Build" shimmie (composer install)
# Done in its own stage so that we don't meed to include all the
# composer fluff in the final image
FROM dev-tools AS build
COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install --no-dev --no-progress
COPY . /app/

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
