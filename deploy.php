<?php
require 'recipe/drupal8.php';

server('prod', '188.166.130.40', 9999)
->user('deploy')
->identityFile()
->stage('production')
->env('deploy_path', '/usr/share/nginx/html/kristiankaadk');

set('repository', 'git@github.com:kaa4ever/kristiankaadk.git');

task('deploy:permissions', function() {
  run('if [ -d {{deploy_path}}/shared ]; then sudo chown -R deploy:deploy {{deploy_path}}/shared; fi');
  run('if [ -d {{deploy_path}}/releases ]; then sudo chown -R deploy:deploy {{deploy_path}}/releases; fi');
});

task('docker:reboot', function () {
  cd('{{release_path}}');
  run('docker stop kristiankaa.site || true');
  run('docker rm kristiankaa.site || true');
  run('docker-compose -f docker-compose.prod.yml up -d');
});

task('drush:composer', function() {
  writeln("<info>Drush: Installing dependencies</info>");
  run('docker exec kristiankaa.site bash -c "cd /var/www/html && composer install"');
});

task('drush:updb', function () {
  writeln("<info>Drush: Updating database</info>");
  run('docker exec kristiankaa.site bash -c "cd /var/www/html/web && ../vendor/drush/drush/drush updb -y --root=/var/www/html/web"');
});

task('drush:updb', function () {
    writeln("<info>Drush: Updating database</info>");
    run('docker exec kristiankaa.site bash -c "cd /var/www/html/web && ../vendor/drush/drush/drush updb -y --root=/var/www/html/web"');
});

task('drush:config', function () {
    writeln("<info>Drush: Synchronizing configuration</info>");
    run('docker exec kristiankaa.site bash -c "cd /var/www/html/web && ../vendor/drush/drush/drush cim --source=./config"');
});

task('drush:cache', function () {
  writeln("<info>Drush: Rebuilding cache</info>");
  run('docker exec kristiankaa.site bash -c "cd /var/www/html/web && ../vendor/drush/drush/drush cr --root=/var/www/html/web"');
});

after('deploy:prepare', 'deploy:permissions');
after('deploy:update_code', 'docker:reboot');
after('deploy:update_code', 'drush:composer');
after('deploy', 'drush:updb');
after('deploy', 'drush:config');
after('deploy', 'drush:cache');
