ARG PHP_VERSION=8.4

# Tree of layers:
# base
# ├── dev-tools
# │   ├── build
# │   └── devcontainer
# └── run (copies built artifacts out of build)

# Install base packages
# Things which all stages (build, test, run) need
FROM debian:trixie AS base
COPY --from=mwader/static-ffmpeg:7.1 /ffmpeg /ffprobe /usr/local/bin/
RUN apt update && \
    apt upgrade -y && \
    apt install -y curl && \
    apt update && apt install -y --no-install-recommends \
    supervisor \
    nginx \
    php${PHP_VERSION}-cli php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-gd php${PHP_VERSION}-zip php${PHP_VERSION}-xml php${PHP_VERSION}-mbstring php${PHP_VERSION}-curl \
    php${PHP_VERSION}-pgsql php${PHP_VERSION}-mysql php${PHP_VERSION}-sqlite3 \
    php${PHP_VERSION}-memcached \
    curl imagemagick zip unzip librsvg2-bin git && \
    rm -rf /var/lib/apt/lists/*

# Install dev packages
# Things which are only needed during development - Composer has 100MB of
# dependencies, so let's avoid including that in the final image
FROM base AS dev-tools
RUN apt update && apt upgrade -y && \
    apt install -y composer php${PHP_VERSION}-xdebug procps net-tools vim && \
    rm -rf /var/lib/apt/lists/*
ENV XDEBUG_MODE=coverage

# "Build" shimmie (composer install)
# Done in its own stage so that we don't meed to include all the
# composer fluff in the final image
FROM dev-tools AS build
COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install --no-dev --no-progress --optimize-autoloader
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
ARG BUILD_TIME=unknown
ARG BUILD_HASH=unknown
ENV UID=1000
ENV GID=1000
ENV SHM_NICE_URLS=true
COPY --from=build /app /app
WORKDIR /app
RUN echo "define('BUILD_TIME', '$BUILD_TIME');" >> core/Config/SysConfig.php && \
    echo "define('BUILD_HASH', '$BUILD_HASH');" >> core/Config/SysConfig.php
ENTRYPOINT ["/app/.docker/entrypoint.sh"]
CMD ["php", "/app/.docker/run.php"]
