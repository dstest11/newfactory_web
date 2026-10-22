<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// This bootstrap only ever serves the test suite: pin the environment instead of
// trusting whatever the shell/container exports (the dev container has APP_ENV=dev,
// which made WebTestCase fail with "framework.test is not set to true").
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'test';
putenv('APP_ENV=test');

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

// The dev container (nginx_fpm_shared) also carries the REAL NEWFACTORY_* values
// (Strapi token, editor allowlist + password hash) in its process env, and Dotenv
// never overrides a variable that is already set — so .env.test was silently
// ignored locally: the repositories talked to the live Strapi (tests saw edited
// product names) and the editor login used the real hash. CI has no such env, so
// it only ever failed on a developer machine. Let the test stubs win.
(new Dotenv())->overload(dirname(__DIR__).'/.env.test');

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
