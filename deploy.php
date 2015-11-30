<?php

// All Deployer recipes are based on `recipe/common.php`.
require 'recipe/drupal8.php';

// Define a server for deployment.
// Let's name it "prod" and use port 22.
server('prod', '46.101.170.221', 9999)
->user('kka')
->identityFile()
->stage('production')
->env('deploy_path', '/var/www'); // Define the base path to deploy your project to.

// Specify the repository from which to download your project's code.
// The server needs to have git installed for this to work.
// If you're not using a forward agent, then the server has to be able to clone
// your project from this repository.
set('repository', 'git@github.com:kaa4ever/kristiankaadk.git');

task('build', function() {
  write('Starting to build site');
  cd('/var/www/html');
  run('composer install');
});

task('drush', function() {
  write('Updating database');
  cd('/var/www/html');
  run('drush updb -y');
  write('Rebuilding cache');
  run('drush cr');
});

after('deploy', 'build');
after('deploy', 'drush');
