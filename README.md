# Docker - PHP, Nginx and MariaDB in separate containers.
This is a basic template for running a Docker setup, geared towards Drupal 8 development.

It's a test setup, and not meant for any production environments. As the title tells, PHP, Nginx, and MariaDB each has it own container, which cleary has some advantages.

## How-to

* Clone this repository
* Run "docker-compose up"

Everything in the "site" folder, will be served by nginx :-)

## Known issue (Docker shared volumes file permission issue)
The PHP-FPM [configuration file](https://github.com/kaa4ever/docker-init/blob/master/config/php-fpm.conf) includes these two lines:

```
 user = root
 group = root
```

Which makes php execute scripts as root. Why is this? Some permission issues exists when PHP and NGINX are not in the same container. PHP runs as user www-data by default, but this user does not have write permission in the shared folders/volumes.
This is a critical issue when trying to run Drupal.

Spending almost an entire day of Googling told me that this is a well known issue, with some different, hacky solutions.
I find this way the less hackiest, altho also the less production ready solution.

Fell free to propose better solutions or correct me if im wrong.
