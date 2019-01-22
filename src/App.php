<?php

namespace Nails\Console;

use Nails\Common\Service\ErrorHandler;
use Nails\Components;
use Nails\Console\Utf8;
use Nails\Factory;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class App
{
    /**
     * Executes the console app
     *
     * @param string               $sEntryPoint      The path of the route index.php file
     * @param InputInterface|null  $oInputInterface  The input interface to use
     * @param OutputInterface|null $oOutputInterface The output interface to use
     * @param bool                 $bAutoExit        Whether to auto-exit from the application or not
     *
     * @throws \Nails\Common\Exception\FactoryException
     */
    public function go(
        $sEntryPoint,
        InputInterface $oInputInterface = null,
        OutputInterface $oOutputInterface = null,
        $bAutoExit = true
    ) {
        /*
         *---------------------------------------------------------------
         * App Bootstrapper: preSystem
         *---------------------------------------------------------------
         * Allows the app to execute code very early on in the console tool lifecycle
         */
        if (class_exists('App\Console\Bootstrap') && is_callable('\App\Console\Bootstrap::preSystem')) {
            \App\Console\Bootstrap::preSystem($this);
        }


        /*
         *---------------------------------------------------------------
         * Nails Bootstrapper
         *---------------------------------------------------------------
         */
        \Nails\Bootstrap::run($sEntryPoint);


        /*
         *---------------------------------------------------------------
         * Command line can run forever
         *---------------------------------------------------------------
         */
        set_time_limit(0);


        /*
         *---------------------------------------------------------------
         * Instantiate CI's Utf8 library; so we have the appropriate
         * constants defined
         *---------------------------------------------------------------
         */
        $oUtf8 = new Utf8();


        /*
         *---------------------------------------------------------------
         * CLI only
         *---------------------------------------------------------------
         */
        $oInput = Factory::service('Input');
        if (!$oInput::isCli()) {
            ErrorHandler::halt('This tool can only be used on the command line.');
        }


        /*
         *---------------------------------------------------------------
         * Instantiate the application
         *---------------------------------------------------------------
         */
        $oApp = new Application();


        /*
         *---------------------------------------------------------------
         * Set auto-exit behaviour
         *---------------------------------------------------------------
         */
        $oApp->setAutoExit($bAutoExit);


        /*
         *---------------------------------------------------------------
         * Command Locations
         *---------------------------------------------------------------
         * Define which directories to look in for Console Commands
         */

        $aCommandLocations = [
            [NAILS_APP_PATH . 'vendor/nails/common/src/Common/Console/Command/', 'Nails\Common\Console\Command'],
            [NAILS_APP_PATH . 'src/Console/Command/', 'App\Console\Command'],
        ];

        $aModules = Components::modules();
        foreach ($aModules as $oModule) {
            $aCommandLocations[] = [
                $oModule->path . 'src/Console/Command',
                $oModule->namespace . 'Console\Command',
            ];
        }


        /*
         *---------------------------------------------------------------
         * Load Commands
         *---------------------------------------------------------------
         * Recursively look for commands
         */

        Factory::helper('directory');
        $aCommands = [];

        function findCommands(&$aCommands, $sPath, $sNamespace)
        {
            $aDirMap = directory_map($sPath);
            if (!empty($aDirMap)) {
                foreach ($aDirMap as $sDir => $sFile) {
                    if (is_array($sFile)) {
                        findCommands($aCommands, $sPath . DIRECTORY_SEPARATOR . $sDir, $sNamespace . '\\' . trim($sDir, '/'));
                    } else {
                        $aFileInfo   = pathinfo($sFile);
                        $sFileName   = basename($sFile, '.' . $aFileInfo['extension']);
                        $aCommands[] = $sNamespace . '\\' . $sFileName;
                    }
                }
            }
        }

        foreach ($aCommandLocations as $aLocation) {
            list($sPath, $sNamespace) = $aLocation;
            findCommands($aCommands, $sPath, $sNamespace);
        }

        foreach ($aCommands as $sCommandClass) {
            $oApp->add(new $sCommandClass());
        }


        /*
         *---------------------------------------------------------------
         * App Bootstrapper: preCommand
         *---------------------------------------------------------------
         * Allows the app to execute code just before the command is called
         */
        if (class_exists('App\Console\Bootstrap') && is_callable('\App\Console\Bootstrap::preCommand')) {
            \App\Console\Bootstrap::preCommand($this);
        }


        /*
         *---------------------------------------------------------------
         * Run the application
         *---------------------------------------------------------------
         * Go, go, go!
         */
        $oApp->run($oInputInterface, $oOutputInterface);


        /*
         *---------------------------------------------------------------
         * App Bootstrapper: postCommand
         *---------------------------------------------------------------
         * Allows the app to execute code just after the command is called
         */
        if (class_exists('App\Console\Bootstrap') && is_callable('\App\Console\Bootstrap::postCommand')) {
            \App\Console\Bootstrap::postCommand($this);
        }


        /*
         *---------------------------------------------------------------
         * App Bootstrapper: postSystem
         *---------------------------------------------------------------
         * Allows the app to execute code at the very end of the console tool lifecycle
         */
        if (class_exists('App\Console\Bootstrap') && is_callable('\App\Console\Bootstrap::postSystem')) {
            \App\Console\Bootstrap::postSystem($this);
        }
    }
}
