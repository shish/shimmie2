# Tree of layers:
# base
# ├── dev-tools
# │   ├── build
# │   └── devcontainer
# └── run (copies built artifacts out of build)

# Install base packages
# Things which all stages (build, test, run) need
FROM dunglas/frankenphp:1.12.7-php8.5-trixie AS base
COPY --from=docker.io/mwader/static-ffmpeg:7.1 /ffmpeg /ffprobe /usr/local/bin/
#RUN install-php-extensions gd zip xml mbstring curl pdo_pgsql pdo_mysql pdo_sqlite3 memcached
RUN apt update && \
    apt upgrade -y && \
    apt install -y --no-install-recommends \
        curl imagemagick zip unzip librsvg2-bin git && \
    rm -rf /var/lib/apt/lists/*

# "Build" shimmie (composer install)
# Done in its own stage so that we don't meed to include all the
# composer fluff in the final image
FROM base AS build
COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install --no-dev --no-progress --optimize-autoloader
COPY . /app/

# Devcontainer target
# Contains all of the build and debug tools, but no code, since
# that's mounted from the host
FROM dev-tools AS devcontainer
EXPOSE 8000
ENV SERVER_NAME=:8000
ENTRYPOINT ["/app/.docker/entrypoint.sh"]
CMD ["php", "/app/.docker/run.php"]

# Actually run shimmie
FROM base AS run
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
