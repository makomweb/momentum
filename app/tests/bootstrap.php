<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Ensure APP_ENV=test is set BEFORE loading .env files
// phpunit.dist.xml sets this in $_SERVER, but Symfony reads from $_ENV
$_ENV['APP_ENV'] = 'test';
if (!isset($_SERVER['APP_ENV'])) {
    $_SERVER['APP_ENV'] = 'test';
}

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
