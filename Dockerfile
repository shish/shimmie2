FROM debian:stable-slim
ENV DEBIAN_FRONTEND=noninteractive
EXPOSE 8000
RUN apt update && apt install -y curl
HEALTHCHECK --interval=5m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1

RUN apt install -y php7.3-cli php7.3-gd php7.3-pgsql php7.3-mysql php7.3-sqlite3 php7.3-zip php7.3-dom php7.3-mbstring php-xdebug
RUN apt install -y composer imagemagick vim zip unzip

COPY composer.json /app/
WORKDIR /app
RUN composer install

COPY . /app/
RUN mkdir -p data/config && \
    echo "<?php define(\"DATABASE_DSN\", \"sqlite:data/shimmie.sqlite\");" > data/config/auto_install.conf.php && \
    php index.php && \
    ./vendor/bin/phpunit --configuration tests/phpunit.xml --coverage-text && \
    rm -rf data
CMD "/app/tests/docker-init.sh"
