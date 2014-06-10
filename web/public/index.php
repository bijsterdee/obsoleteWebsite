<?php

use FaynticServices\Website\Application;

if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require __DIR__ . '/../../vendor/autoload.php';

    (new Application())->run();
} else {
    header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error", true, 500);
}
