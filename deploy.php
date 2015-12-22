<?php

// All Deployer recipes are based on `recipe/common.php`.
require 'recipe/drupal8.php';

// Define a server for deployment.
// Let's name it "prod" and use port 22.
server('prod', '46.101.170.221', 9999)
->user('kka')
->identityFile()
->stage('production')
->env('deploy_path', '/usr/share/nginx/kristiankaadk'); // Define the base path to deploy your project to.

// Specify the repository from which to download your project's code.
// The server needs to have git installed for this to work.
// If you're not using a forward agent, then the server has to be able to clone
// your project from this repository.
set('repository', 'git@github.com:kaa4ever/kristiankaadk.git');

// Set the symlinks.
task('deploy:symlink', function () {
  cd('{{release_path}}/site/sites/default');
  run('ln -s /usr/share/nginx/kristiankaadk/shared/sites/default/settings.php');
  run('ln -s /usr/share/nginx/kristiankaadk/shared/sites/default/services.yml');
  run('ln -s /usr/share/nginx/kristiankaadk/shared/sites/default/files');
  run('rm /usr/share/nginx/kristiankaadk/current');
  run('ln -s {{release_path}} /usr/share/nginx/kristiankaadk/current');
})->desc('Setting symlinks');

task('build', function() {
  cd('{{release_path}}/site');
  run('drush make site.make -y');
});

task('drush', function() {
  write('Updating database');
  cd('/usr/share/nginx/kristiankaadk/current/site');
  run('drush updb -y');
  write('Rebuilding cache');
  run('drush cr');
});

after('deploy:shared', 'build');
after('deploy', 'drush');
