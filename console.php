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

use Nails\Startup;
use Nails\Factory;
use Symfony\Component\Console\Application;

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

if (!function_exists('_NAILS_ERROR')) {

    function _NAILS_ERROR($error, $subject = '')
    {
        echo '<style type="text/css">';
            echo 'p {font-family:monospace;margin:20px 10px;}';
            echo 'strong { color:red;}';
            echo 'code { padding:5px;border:1px solid #CCC;background:#EEE }';
        echo '</style>';
        echo '<p>';
            echo '<strong>ERROR:</strong> ';
            echo $subject ? '<em>' . $subject . '</em> - ' : '';
            echo $error;
        echo '</p>';
        exit(0);
    }
}

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
Factory::setup();

//  Set to run indefinitely
set_time_limit(0);

//  Make sure we're running on UTC
date_default_timezone_set('UTC');

//  Only allow the console to run whilst on the CLI
if (!isCli()) {
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

//  Get common Apps
$aApps = array(
    'Nails\Common\Console\Command\Deploy',
    'Nails\Common\Console\Command\Install',
    'Nails\Common\Console\Command\Migrate',
    'Nails\Common\Console\Command\Test'
);

//  Look for apps provided by the modules
$aModules = _NAILS_GET_MODULES();
foreach ($aModules as $oModule) {
    //  @todo
}

//  Instantiate and run the application
$app = new Application();
foreach ($aApps as $sClass) {
    $app->add(new $sClass());
}
$app->run();
