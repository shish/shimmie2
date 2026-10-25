# Tree of layers:
# base (basic OS packages which all stages need)
# ├── devcontainer (plus dev packages)
# ├── build (composer install)
# └── run (copies built artifacts out of build, but leaves composer fluff behind)

# Install base packages
# Things which all stages (build, test, run) need
FROM dunglas/frankenphp:1.13.3-php8.5-trixie AS base
COPY --from=docker.io/mwader/static-ffmpeg:9.0 /ffmpeg /ffprobe /usr/local/bin/
RUN install-php-extensions gd zip xml mbstring curl pdo_pgsql pdo_mysql pdo_sqlite memcached pcntl
RUN apt update && \
    apt upgrade -y && \
    apt install -y --no-install-recommends \
        curl imagemagick zip unzip librsvg2-bin ca-certificates && \
    rm -rf /var/lib/apt/lists/*

# "Build" shimmie (composer install)
# Done in its own stage so that we don't meed to include all the
# composer fluff in the final image
FROM base AS build
RUN apt update && \
    apt upgrade -y && \
    apt install -y --no-install-recommends git && \
    rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install --no-dev --no-progress --optimize-autoloader
COPY . /app/

# Devcontainer target
# Contains all of the build and debug tools, but no code, since
# that's mounted from the host
FROM base AS devcontainer
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN install-php-extensions xdebug
EXPOSE 8000
ENV SERVER_NAME=:8000
ENTRYPOINT ["/app/.docker/entrypoint.sh"]
CMD ["php", "/app/.docker/run.php"]

# Actually run shimmie, using "scratch" and then copying all the files
# to avoid inheriting EXPOSE from frankenphp
FROM scratch AS run
COPY --from=base / /
EXPOSE 8000
ENV SERVER_NAME=:8000
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
