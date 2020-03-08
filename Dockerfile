FROM debian:stable-slim
ENV DEBIAN_FRONTEND=noninteractive
EXPOSE 8000
RUN apt update && apt install -y curl
HEALTHCHECK --interval=5m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1

RUN apt install -y php7.3-cli php7.3-gd php7.3-pgsql php7.3-mysql php7.3-sqlite3 php7.3-zip php7.3-dom php7.3-mbstring php-xdebug
RUN apt install -y composer imagemagick vim zip unzip

COPY composer.json composer.lock /app/
WORKDIR /app
RUN composer install

COPY . /app/
RUN echo '=== Installing ===' && mkdir -p data/config && echo "<?php \$dsn = \"sqlite:data/shimmie.sqlite\";" > data/config/auto_install.conf.php && php index.php && \
    echo '=== Smoke Test ===' && php index.php get-page /post/list && \
    echo '=== Unit Tests ===' && ./vendor/bin/phpunit --configuration tests/phpunit.xml && \
    echo '=== Coverage ===' && ./vendor/bin/phpunit --configuration tests/phpunit.xml --coverage-text && \
    echo '=== Cleaning ===' && rm -rf data
RUN chmod +x /app/tests/docker-init.sh
CMD "/app/tests/docker-init.sh"
