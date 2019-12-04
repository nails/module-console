<?php

namespace Nails\Console;

use Nails\Bootstrap;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Helper\Directory;
use Nails\Common\Service\ErrorHandler;
use Nails\Common\Service\Event;
use Nails\Components;
use Nails\Factory;
use ReflectionClass;
use ReflectionException;
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
     * @throws FactoryException
     * @throws NailsException
     * @throws ReflectionException
     */
    public function go(
        $sEntryPoint,
        InputInterface $oInputInterface = null,
        OutputInterface $oOutputInterface = null,
        $bAutoExit = true
    ) {
        /*
         *---------------------------------------------------------------
         * Nails Bootstrapper
         *---------------------------------------------------------------
         */
        Bootstrap::run($sEntryPoint);

        /*
         *---------------------------------------------------------------
         * Events: Startup
         *---------------------------------------------------------------
         */
        /** @var Event $oEventService */
        $oEventService = Factory::service('Event');
        $oEventService
            ->trigger(Events::STARTUP, Events::getEventNamespace());

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
        $oApp = new Application('Nails Command Line Tool');

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
        ];

        foreach (Components::available() as $oModule) {
            $aNamespacePaths = $oModule->getNamespaceRootPaths();
            foreach ($aNamespacePaths as $sNamespacePath) {
                $aCommandLocations[] = [
                    $sNamespacePath . '/Console/Command/',
                    $oModule->namespace . 'Console\\Command',
                ];
            }
        }

        /*
         *---------------------------------------------------------------
         * Load Commands
         *---------------------------------------------------------------
         * Recursively look for commands
         */

        $aCommands = [];

        foreach ($aCommandLocations as $aLocation) {

            [$sPath, $sNamespace] = $aLocation;

            $aDirMap = Directory::map($sPath, null, false);

            foreach ($aDirMap as $sDir => $sFile) {

                $aFileInfo   = pathinfo($sFile);
                $aCommands[] = implode(
                    '\\',
                    array_filter(
                        array_merge(
                            [$sNamespace],
                            $aFileInfo['dirname'] !== '.'
                                ? explode(DIRECTORY_SEPARATOR, $aFileInfo['dirname'])
                                : [],
                            [$aFileInfo['filename']]
                        )
                    )
                );
            }
        }

        foreach ($aCommands as $sCommandClass) {
            $oReflection = new ReflectionClass($sCommandClass);
            if ($oReflection->isInstantiable()) {
                $oApp->add(new $sCommandClass());
            }
        }

        /*
         *---------------------------------------------------------------
         * Events: Ready
         *---------------------------------------------------------------
         */
        $oEventService
            ->trigger(Events::READY, Events::getEventNamespace());

        /*
         *---------------------------------------------------------------
         * Run the application
         *---------------------------------------------------------------
         * Go, go, go!
         */
        $oApp->run($oInputInterface, $oOutputInterface);

        /*
         *---------------------------------------------------------------
         * Nails Shutdown Handler
         *---------------------------------------------------------------
         */
        Bootstrap::shutdown();

        /*
         *---------------------------------------------------------------
         * Events: Shutdown
         *---------------------------------------------------------------
         */
        $oEventService
            ->trigger(Events::SHUTDOWN, Events::getEventNamespace());
    }
}
