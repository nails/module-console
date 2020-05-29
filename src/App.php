<?php

namespace Nails\Console;

use Nails\Bootstrap;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Service\ErrorHandler;
use Nails\Common\Service\Event;
use Nails\Components;
use Nails\Factory;
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
         * Load commands
         *---------------------------------------------------------------
         */

        foreach (Components::available() as $oComponent) {

            $aClasses = $oComponent
                ->findClasses('Console\\Command')
                ->whichExtend(\Symfony\Component\Console\Command\Command::class);

            foreach ($aClasses as $sClass) {
                $oApp->add(new $sClass());
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
