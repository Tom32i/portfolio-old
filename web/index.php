<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;
use Tom32i\Phpillip\Application;

Debug::enable();

$app = new Application(['environment' => 'dev', 'debug' => true]);
$app->run();
