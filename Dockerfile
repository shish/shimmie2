FROM debian:testing-slim
ENV DEBIAN_FRONTEND=noninteractive
EXPOSE 8000
RUN apt update && apt install -y curl
HEALTHCHECK --interval=5m --timeout=3s CMD curl --fail http://127.0.0.1:8000/ || exit 1

RUN apt install -y php7.3-cli php7.3-gd php7.3-pgsql php7.3-mysql php7.3-sqlite3 php7.3-zip php7.3-dom php7.3-mbstring php-xdebug
RUN apt install -y composer imagemagick vim

COPY composer.json /app/
WORKDIR /app
RUN mkdir -p data/config
RUN composer install

COPY . /app/
# RUN psql -c "SELECT set_config('log_statement', 'all', false);" -U postgres ;
# RUN psql -c "CREATE DATABASE shimmie;" -U postgres ;
# RUN echo '<?php define("DATABASE_DSN", "pgsql:user=postgres;password=;host=;dbname=shimmie");' > data/config/auto_install.conf.php ;
# RUN mysql -e "SET GLOBAL general_log = 'ON';" -uroot ;
# RUN mysql -e "CREATE DATABASE shimmie;" -uroot ;
# RUN echo '<?php define("DATABASE_DSN", "mysql:user=root;password=;host=localhost;dbname=shimmie");' > data/config/auto_install.conf.php ;
RUN echo '<?php define("DATABASE_DSN", "sqlite:shimmie.sqlite");' > data/config/auto_install.conf.php
RUN php index.php
# RUN ./vendor/bin/phpunit --configuration tests/phpunit.xml --coverage-text
CMD ["/usr/bin/php", "-d", "upload_max_filesize=50M", "-d", "post_max_size=50M", "-S", "0.0.0.0:8000", "tests/router.php"]
