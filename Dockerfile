FROM php:7.4.21-fpm-alpine3.13

RUN apk add --no-cache bash
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/local/bin
RUN ls -lah /usr/local/bin
RUN wget https://get.symfony.com/cli/installer -O - | bash

ADD . .
COPY www.conf /usr/local/etc/php-fpm.d/www.conf
RUN composer.phar install
RUN $HOME/.symfony/bin/symfony check:requirements

