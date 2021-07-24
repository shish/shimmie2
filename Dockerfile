# "Build" shimmie (composer install - done in its own stage so that we don't
# need to include all the composer fluff in the final image)
FROM debian:testing-slim AS app
RUN apt update && apt install -y composer php7.4-gd php7.4-dom php7.4-sqlite3 php-xdebug imagemagick
COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install --no-dev
COPY . /app/

# Tests in their own image. Really we should inherit from app and then
# `composer install` phpunit on top of that; but for some reason
# `composer install --no-dev && composer install` doesn't install dev
FROM debian:testing-slim AS tests
RUN apt update && apt install -y composer php7.4-gd php7.4-dom php7.4-sqlite3 php-xdebug imagemagick
COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install
COPY . /app/
ARG RUN_TESTS=true
RUN [ $RUN_TESTS = false ] || (\
    echo '=== Installing ===' && mkdir -p data/config && INSTALL_DSN="sqlite:data/shimmie.sqlite" php index.php && \
    echo '=== Smoke Test ===' && php index.php get-page /post/list && \
    echo '=== Unit Tests ===' && ./vendor/bin/phpunit --configuration tests/phpunit.xml && \
    echo '=== Coverage ===' && ./vendor/bin/phpunit --configuration tests/phpunit.xml --coverage-text && \
    echo '=== Cleaning ===' && rm -rf data)

# Build su-exec so that our final image can be nicer
FROM debian:testing-slim AS suexec
RUN  apt-get update && apt-get install -y --no-install-recommends gcc libc-dev curl
RUN  curl -k -o /usr/local/bin/su-exec.c https://raw.githubusercontent.com/ncopa/su-exec/master/su-exec.c; \
     gcc -Wall /usr/local/bin/su-exec.c -o/usr/local/bin/su-exec; \
     chown root:root /usr/local/bin/su-exec; \
     chmod 0755 /usr/local/bin/su-exec;

# Actually run shimmie
FROM debian:testing-slim
EXPOSE 8000
HEALTHCHECK --interval=1m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1
ENV UID=1000 \
    GID=1000
RUN apt update && apt install -y curl \
    php7.4-cli php7.4-gd php7.4-pgsql php7.4-mysql php7.4-sqlite3 php7.4-zip php7.4-dom php7.4-mbstring \
    imagemagick zip unzip && \
    rm -rf /var/lib/apt/lists/*
COPY --from=app /app /app
COPY --from=suexec /usr/local/bin/su-exec /usr/local/bin/su-exec

WORKDIR /app
CMD ["/bin/sh", "/app/tests/docker-init.sh"]
