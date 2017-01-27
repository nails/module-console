<?php

namespace Nails\Console\Command;

use Nails\Common\Exception\NailsException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseAlias extends Base
{
    /**
     * The command to execute
     */
    const COMMAND = '';

    /**
     * The command to respond to
     */
    const ALIAS   = '';

    // --------------------------------------------------------------------------

    /**
     * Configures the app
     *
     * @throws NailsException
     * @return void
     */
    protected function configure()
    {
        if (empty(static::COMMAND)) {
            throw new NailsException('static::COMMAND must be defined');
        }

        if (empty(static::ALIAS)) {
            throw new NailsException('static::ALIAS must be defined');
        }

        // --------------------------------------------------------------------------

        $this->setName(static::ALIAS);
        $this->setDescription('Alias to <info>' . static::COMMAND . '</info>');
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param  InputInterface $oInput The Input Interface provided by Symfony
     * @param  OutputInterface $oOutput The Output Interface provided by Symfony
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        parent::execute($oInput, $oOutput);

        $aArguments = $oInput->getArguments();
        //  This is re-set in callCommand()
        unset($aArguments['command']);

        $aOptions = [];
        foreach ($oInput->getOptions() as $k => $v) {
            $aOptions['--' . $k] = $v;
        }

        // --------------------------------------------------------------------------

        //  Symfony seems to ignore calls to --no-interaction, so if it has been passed
        //  remove it and use the callCommand() argument instead
        $bInteractive = true;
        if (!empty($aOptions['--no-interaction'])) {
            unset($aOptions['--no-interaction']);
            $bInteractive = false;
        }

        // --------------------------------------------------------------------------

        return $this->callCommand(static::COMMAND, $aArguments + $aOptions, $bInteractive);
    }
}
