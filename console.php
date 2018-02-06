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

use Nails\Common\Library\Input;
use Nails\Startup;
use Nails\Factory;
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

$system_path = 'vendor/rogeriopradoj/codeigniter/system';
if (realpath($system_path) !== false) {
    $system_path = realpath($system_path) . '/';
}
$system_path = rtrim($system_path, '/') . '/';
define('BASEPATH', str_replace("\\", "/", $system_path));

define('FCPATH', realpath(str_replace(SELF, '', __FILE__) . '../../../') . '/');

define('APPPATH', 'application/');

// --------------------------------------------------------------------------
//  Above copied from CodeIgniter's index.php
// --------------------------------------------------------------------------
// --------------------------------------------------------------------------

//  Detect if FCPATH resolves to the same location as this file, if it doesn't then
//  Nails is probably being run as a symlink and must be halted.
if (FCPATH . 'vendor/nailsapp/module-console/console.php' !== __FILE__) {
    _NAILS_ERROR(
        'console.php does not reside where it is expected (in the app\'s vendor/nailsapp folder). ' .
        'This might be because you have symlinked the vendor/nailsapp directory (useful when developing) '.
        'This however causes loading errors and must be rectified.',
        'Invalid location of console.php'
    );
}

//  Set the working directory so that requires etc work as they do in the mian application
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
require_once NAILS_COMMON_PATH . 'core/CORE_NAILS_Common.php';
require_once NAILS_COMMON_PATH . 'src/Startup.php';
$oStartup = new Startup();
$oStartup->init();

//  Set to run indefinitely
set_time_limit(0);

//  Make sure we're running on UTC
date_default_timezone_set('UTC');

//  Only allow the console to run whilst on the CLI
if (!Input::isCli()) {
    echo 'This tool can only be used on the command line.';
    exit(1);
}

//  Setup error handling
Factory::service('ErrorHandler');

// --------------------------------------------------------------------------

//  Set Common and App locations
$aAppLocations = array(
    array(FCPATH . 'vendor/nailsapp/common/src/Common/Console/Command/', 'Nails\Common\Console\Command'),
    array(FCPATH . 'src/Console/Command/', 'App\Console\Command')
);

//  Look for apps provided by the modules
$aModules = _NAILS_GET_MODULES();
foreach ($aModules as $oModule) {
    $aAppLocations[] = array(
        $oModule->path . 'src/Console/Command',
        $oModule->namespace . 'Console\Command'
    );
}

Factory::helper('directory');
$aApps = array();

function findCommands(&$aApps, $sPath, $sNamespace)
{
    $aDirMap = directory_map($sPath);
    if (!empty($aDirMap)) {
        foreach ($aDirMap as $sDir => $sFile) {
            if (is_array($sFile)) {
                findCommands($aApps, $sPath . DIRECTORY_SEPARATOR . $sDir, $sNamespace . '\\' . $sDir);
            } else {
                $aFileInfo = pathinfo($sFile);
                $sFileName =  basename($sFile, '.' . $aFileInfo['extension']);
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
