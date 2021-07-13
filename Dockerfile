FROM katapultman/php7.4-grpc:0.2

COPY . /usr/src/Hobbyfi-API
WORKDIR /usr/src/Hobbyfi-API

COPY 000-default.conf /etc/apache2/sites-available/000-default.conf
COPY ports.conf /etc/apache2/ports.conf
COPY start-apache /usr/local/bin

RUN a2enmod rewrite

RUN apt-get update && apt-get install -y \
    sendmail \
    zlib1g-dev \
    libpng-dev \
    libzip-dev \
    unzip

RUN apt-get update && \
    apt-get upgrade -y && \
    apt-get install -y git

RUN cd /tmp && curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

RUN docker-php-ext-install zip mysqli gd

RUN composer install

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2
ENV APACHE_LOCK_DIR /var/lock/apache2
ENV APACHE_PID_FILE /var/run/apache2.pid

CMD ["start-apache"]