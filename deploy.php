<?php
require 'recipe/drupal8.php';

server('prod', '146.185.128.63', 9999)
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

//task('deploy:symlink', function () {
//  cd('{{release_path}}/site/sites/default');
//  run('ln -s /usr/share/nginx/kristiankaadk/shared/sites/default/settings.php');
//  run('ln -s /usr/share/nginx/kristiankaadk/shared/sites/default/services.yml');
//  run('ln -s /usr/share/nginx/kristiankaadk/shared/sites/default/files');
//  run('rm /usr/share/nginx/kristiankaadk/current');
//  run('ln -s {{release_path}} /usr/share/nginx/kristiankaadk/current');
//})->desc('Setting symlinks');

task('drush:make', function() {
  writeln("<info>Drush: Building site</info>");
  run('docker exec kristiankaa.site bash -c "cd /var/www/html && drush make site.make -y"');
});


task('drush:updb', function () {
  writeln("<info>Drush: Updating database</info>");
  run('docker exec kristiankaa.site drush updb -y --root=/var/www/html');
});

task('drush:cache', function () {
  writeln("<info>Drush: Rebuilding cache</info>");
  run('docker exec kristiankaa.site drush cr --root=/var/www/html');
});

after('deploy:prepare', 'deploy:permissions');
after('deploy:update_code', 'docker:reboot');
after('deploy:update_code', 'drush:make');
after('deploy', 'drush:updb');
after('deploy', 'drush:cache');
//after('deploy:shared', 'build');
//after('deploy', 'drush');
