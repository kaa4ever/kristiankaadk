FROM million12/nginx-php

MAINTAINER Kristian Kaa <kaakristian@gmail.com>

# Copy all files.
COPY site /data/www
COPY config/default.conf /etc/nginx/nginx.d/default.conf
RUN usermod -u 1000 www
