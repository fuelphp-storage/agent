<?php

/**
 * Updates browscap cache to make unit tests faster
 */

require __DIR__.'/../vendor/autoload.php';

use phpbrowscap\Browscap;

$browscap = new Browscap(__DIR__.'/_output');

$browscap->userAgent = 'FuelPHP Agent library - Browscap class (http://fuelphp.com)';

$browscap->updateCache();
