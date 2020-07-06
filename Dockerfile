FROM php:7.1-cli

MAINTAINER Vitaliy Zhuk <v.zhuk@fivelab.org>

RUN \
	apt-get update && \
	apt-get install -y --no-install-recommends \
		zip unzip \
		git

RUN \
    apt-get install -y --no-install-recommends librabbitmq-dev && \
    printf "\n" | pecl install amqp && \
    docker-php-ext-enable amqp

# Install composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

WORKDIR /code
