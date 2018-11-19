<?php

/**
 * ---------------------------------------------------------------
 * NAILS CONSOLE
 * ---------------------------------------------------------------
 *
 * This is the console application for Nails.
 * Documentation: https://nailsapp.co.uk/console
 */

namespace Nails\Common\Console;

use Nails\Console\App;

/*
 *---------------------------------------------------------------
 * Autoloader
 *---------------------------------------------------------------
 */
$sEntryPoint = realpath(__DIR__ . '/../../../') . '/index.php';
require_once dirname($sEntryPoint) . '/vendor/autoload.php';

/*
 *---------------------------------------------------------------
 * Run App
 *---------------------------------------------------------------
 */
$oApp = new App();
$oApp->go($sEntryPoint);
