<?php

use Symfony\Component\HttpFoundation\Request;

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';
if (PHP_VERSION_ID < 70000) {
    include_once __DIR__.'/../var/bootstrap.php.cache';
}

$env = strtolower(getenv('APP_ENV'));
if (!$env) {
    $env = 'dev';
}
$debug = 'dev' === $env;

if ('prod' === $env) {
    $kernel = new AppKernel('prod', $debug);
} else {
    $kernel = new AppKernel('dev', $debug);
}

if (PHP_VERSION_ID < 70000 && 'prod' === $env) {
    $kernel->loadClassCache();
}
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();

if (extension_loaded('newrelic')) {
    newrelic_add_custom_parameter('queryString', $request->getQueryString());
}

$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
