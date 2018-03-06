<?php

/**
 * ---------------------------------------------------------------
 * NAILS CONSOLE
 * ---------------------------------------------------------------
 *
 * This is the console application for Nails.
 *
 * Lead Developer: Pablo de la Peña (p@nailsapp.co.uk, @hellopablo)
 * Lead Developer: Gary Duncan      (g@nailsapp.co.uk, @gsdd)
 *
 * Documentation: http://docs.nailsapp.co.uk/console
 */

namespace Nails\Common\Console;

use Nails\Factory;
use Nails\Startup;
use Symfony\Component\Console\Application;

// --------------------------------------------------------------------------

if (!function_exists('_NAILS_ERROR')) {

    function _NAILS_ERROR($sError, $sSubject = '')
    {
        $sSubject = 'ERROR: ' . $sSubject;
        echo "\n\n";
        if (!empty($sSubject)) {
            echo str_repeat('-', strlen($sSubject));
            echo "\n" . strtoupper($sSubject) . "\n";
            echo str_repeat('-', strlen($sSubject));
            echo "\n\n";
        }

        echo wordwrap($sError, 100, "\n");

        echo "\n\n";
        exit(0);
    }
}

// --------------------------------------------------------------------------
// --------------------------------------------------------------------------
//  Below copied from CodeIgniter's index.php
//  Various code rely heavily on these constants
// --------------------------------------------------------------------------

if (defined('STDIN')) {
    chdir(dirname(__FILE__));
}

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));

$system_path = 'vendor/codeigniter/framework/system';
if (realpath($system_path) !== false) {
    $system_path = realpath($system_path) . '/';
}
$system_path = rtrim($system_path, '/') . '/';
define('BASEPATH', str_replace("\\", "/", $system_path));

define('FCPATH', $_SERVER['PWD'] . '/');

define('APPPATH', FCPATH . 'application/');

// --------------------------------------------------------------------------
//  Above copied from CodeIgniter's index.php
// --------------------------------------------------------------------------
// --------------------------------------------------------------------------

if (!file_exists(FCPATH . 'vendor/autoload.php')) {
    _NAILS_ERROR('Missing vendor/autoload.php; please run composer install.');
}

require_once FCPATH . 'vendor/autoload.php';

//  Set the working directory so that requires etc work as they do in the main application
chdir(FCPATH);

/*
 *---------------------------------------------------------------
 * APP SETTINGS
 *---------------------------------------------------------------
 *
 * Load app specific settings.
 *
 */

if (file_exists(FCPATH . 'config/app.php')) {
    require FCPATH . 'config/app.php';
}

/*
 *---------------------------------------------------------------
 * DEPLOY SETTINGS
 *---------------------------------------------------------------
 *
 * Load environment specific settings.
 *
 */

if (file_exists(FCPATH . 'config/deploy.php')) {
    require FCPATH . 'config/deploy.php';
}

/*
 *---------------------------------------------------------------
 * GLOBAL CONSTANTS
 *---------------------------------------------------------------
 *
 * These global constants need defined early on, they can be
 * overridden by app.php or deploy.php
 *
 */

if (!defined('NAILS_PATH')) {
    define('NAILS_PATH', realpath(dirname(__FILE__) . '/../') . '/');
}

if (!defined('NAILS_COMMON_PATH')) {
    define('NAILS_COMMON_PATH', realpath(dirname(__FILE__) . '/../common/') . '/');
}

/**
 * Setup the basic system
 */
require_once NAILS_COMMON_PATH . 'src/Common/CodeIgniter/Core/Common.php';
require_once NAILS_COMMON_PATH . 'src/Startup.php';
$oStartup = new Startup();
$oStartup->init();
Factory::setup();

//  Set to run indefinitely
set_time_limit(0);

//  Make sure we're running on UTC
date_default_timezone_set('UTC');

//  Only allow the console to run whilst on the CLI
$oInput = Factory::service('Input');
if (!$oInput::isCli()) {
    echo 'This tool can only be used on the command line.';
    exit(1);
}

//  Setup error handling
Factory::service('ErrorHandler');

//  Autoload the things
Factory::helper('app_setting');
Factory::helper('app_notification');
Factory::helper('date');
Factory::helper('tools');
Factory::helper('debug');
Factory::helper('language');
Factory::helper('text');
Factory::helper('exception');
Factory::helper('log');

// --------------------------------------------------------------------------

//  Set Common and App locations
$aAppLocations = [
    [FCPATH . 'vendor/nailsapp/common/src/Common/Console/Command/', 'Nails\Common\Console\Command'],
    [FCPATH . 'src/Console/Command/', 'App\Console\Command'],
];

//  Look for apps provided by the modules
$aModules = _NAILS_GET_MODULES();
foreach ($aModules as $oModule) {
    $aAppLocations[] = [
        $oModule->path . 'src/Console/Command',
        $oModule->namespace . 'Console\Command',
    ];
}

Factory::helper('directory');
$aApps = [];

function findCommands(&$aApps, $sPath, $sNamespace)
{
    $aDirMap = directory_map($sPath);
    if (!empty($aDirMap)) {
        foreach ($aDirMap as $sDir => $sFile) {
            if (is_array($sFile)) {
                findCommands($aApps, $sPath . DIRECTORY_SEPARATOR . $sDir, $sNamespace . '\\' . trim($sDir, '/'));
            } else {
                $aFileInfo = pathinfo($sFile);
                $sFileName = basename($sFile, '.' . $aFileInfo['extension']);
                $aApps[]   = $sNamespace . '\\' . $sFileName;
            }
        }
    }
}

foreach ($aAppLocations as $aLocation) {
    list($sPath, $sNamespace) = $aLocation;
    findCommands($aApps, $sPath, $sNamespace);
}

//  Instantiate and run the application
$app = new Application();
foreach ($aApps as $sClass) {
    $app->add(new $sClass());
}
$app->run();
