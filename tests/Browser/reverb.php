<?php

require __DIR__.'/environment.php';
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->handleCommand(new Symfony\Component\Console\Input\ArrayInput(['command' => 'reverb:start', '--host' => '127.0.0.1', '--port' => 8099]));
