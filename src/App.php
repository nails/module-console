<?php

namespace Nails\Console;

use Nails\Common\Service\ErrorHandler;
use Nails\Console\Utf8;
use Nails\Factory;
use Symfony\Component\Console\Application;

final class App
{
    public function go()
    {
        /*
         *---------------------------------------------------------------
         * Command line can run forever
         *---------------------------------------------------------------
         */
        set_time_limit(0);


        /*
         *---------------------------------------------------------------
         * Instanciate CI's Utf8 library; so we have the appropriate
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
            ErrorHandler::die('This tool can only be used on the command line.');
        }


        /*
         *---------------------------------------------------------------
         * Command Locations
         *---------------------------------------------------------------
         *
         * Define which directories to look in for Console Commands
         *
         */

        $aCommandLocations = [
            [FCPATH . 'vendor/nails/common/src/Common/Console/Command/', 'Nails\Common\Console\Command'],
            [FCPATH . 'src/Console/Command/', 'App\Console\Command'],
        ];

        $aModules = _NAILS_GET_MODULES();
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
         *
         * Recursively look for commands
         *
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

        /*
         *---------------------------------------------------------------
         * Instanciate the application
         *---------------------------------------------------------------
         *
         * Instanciate the application and add commands
         *
         */
        $oApp = new Application();
        foreach ($aCommands as $sCommandClass) {
            $oApp->add(new $sCommandClass());
        }

        /*
         *---------------------------------------------------------------
         * App Bootstrapper: preCommand
         *---------------------------------------------------------------
         *
         * Allows the app to execute code just before the command is called
         *
         */
        if (class_exists('App\Console\Bootstrap') && is_callable('\App\Console\Bootstrap::preCommand')) {
            \App\Console\Bootstrap::preCommand();
        }

        /*
         *---------------------------------------------------------------
         * Run the application
         *---------------------------------------------------------------
         *
         * Go, go, go!
         *
         */

        $oApp->run();

        /*
         *---------------------------------------------------------------
         * App Bootstrapper: postCommand
         *---------------------------------------------------------------
         *
         * Allows the app to execute code just after the command is called
         *
         */
        if (class_exists('App\Console\Bootstrap') && is_callable('\App\Console\Bootstrap::postCommand')) {
            \App\Console\Bootstrap::postCommand();
        }

    }
}
