FROM ubuntu:16.04

MAINTAINER Kristian Kaa <kaakristian@gmail.com>

# Update packages and add PHP packages
RUN apt-get update -y
RUN apt-get upgrade -y

RUN apt-get install software-properties-common -y
RUN apt-get install python-software-properties -y
RUN LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y
RUN apt-get update -y

# Install NGINX
RUN apt-get install nginx -y

# Install PHP
RUN apt-get install php7.1 php7.1-fpm php7.1-curl php7.1-zip php7.1-xml php7.1-opcache php7.1-mysql php7.1-mbstring php7.1-bcmath php7.1-mcrypt php7.1-common php7.1-cli php7.1-cgi php7.1-gd php7.1-intl -y
RUN mkdir /run/php

# Install Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '544e09ee996cdf60ece3804abc52599c22b1f40f4323403c44d44fdfdd586475ca9813a858088ffbc1f233e9b180f061') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"

# Install Git
RUN apt-get install git -y

# Install curl
RUN apt-get install curl -y

# Install supervior
RUN apt-get install supervisor -y

COPY ./site /var/www/html
COPY ./config/nginx.conf /etc/nginx/sites-available/default
COPY ./config/php.production.ini /etc/php/7.1/fpm/php.ini
COPY ./config/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

CMD ["/usr/bin/supervisord", "-n"]