<?php
define('TESTS_DIR', __DIR__);
$loader = require __DIR__.'/../vendor/autoload.php';
$loader->add('Level3\Mocks', __DIR__);
$loader->add('Level3\Tests', __DIR__);
