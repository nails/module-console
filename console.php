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
 * App Bootstrapper: preSystem
 *---------------------------------------------------------------
 *
 * Allows the app to execute code very early on in the console tool lifecycle
 *
 */
if (class_exists('App\Console\Bootstrap') && is_callable('\App\Console\Bootstrap::preSystem')) {
    \App\Console\Bootstrap::preSystem();
}


/*
 *---------------------------------------------------------------
 * Nails Bootstrapper
 *---------------------------------------------------------------
 */
\Nails\Bootstrap::run($sEntryPoint);


/*
 *---------------------------------------------------------------
 * Run App
 *---------------------------------------------------------------
 */
$oApp = new App();
$oApp->go();
