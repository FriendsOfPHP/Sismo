<?php

if (is_file(__DIR__.'/../vendor/autoload.php')) {
    require_once __DIR__.'/../vendor/autoload.php';
} elseif (is_file(__DIR__.'/../../../autoload.php')) {
    require_once __DIR__.'/../../../autoload.php';
}

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../src/controllers.php';
$app->run();
