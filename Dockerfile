FROM touchcast/docker-nginx-php7

MAINTAINER Kristian Kaa <kaakristian@gmail.com>

# Copy all files.
COPY site /var/www/html
