<?php
namespace Deployer;

require 'recipe/symfony4.php';

// We don't use a database.
// task('database:migrate', function () {})->setPrivate();

// Project name
set('application', 'iota');

// Project repository
set('repository', 'https://github.com/rimi-itk/iota.git');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
add('shared_files', []);
add('shared_dirs', []);

// Writable dirs by web server
add('writable_dirs', []);


// Hosts
inventory('hosts.yml');

// Tasks
task('build', function () {
    run('cd {{release_path}} && build');
});

// [Optional] if deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

// Migrate database before symlink new release.

// before('deploy:symlink', 'database:migrate');
