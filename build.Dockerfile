FROM php:7.4.21-fpm-alpine3.13

RUN apk add --no-cache bash
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin
RUN apk add --no-cache fcgi

ADD . .
COPY www.conf /usr/local/etc/php-fpm.d/www.conf
RUN composer.phar install --prefer-dist --no-dev
RUN wget -O /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

