<?php

namespace Nails\Console\Command;

use Nails\Console\Exception\Path\DoesNotExistException;
use Nails\Console\Exception\Path\IsNotWritableException;
use Nails\Environment;
use Nails\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseMaker extends Base
{
    /**
     * The permission to write files with
     *
     * @var int
     */
    const FILE_PERMISSION = 0755;

    /**
     * Where resources are stored
     *
     * @var string
     */
    const RESOURCE_PATH = '';

    // --------------------------------------------------------------------------

    /**
     * The passed arguments
     *
     * @var array
     */
    protected $aArguments = [];

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        parent::configure();
        foreach ($this->aArguments as $aArgument) {
            $this->addArgument(
                getFromArray('name', $aArgument),
                getFromArray('mode', $aArgument, InputArgument::OPTIONAL),
                getFromArray('description', $aArgument),
                getFromArray('default', $aArgument)
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param  InputInterface  $oInput  The Input Interface provided by Symfony
     * @param  OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        $oOutput->writeln('');
        $oOutput->writeln('<info>----------------</info>');
        $oOutput->writeln('<info>Nails Maker Tool</info>');
        $oOutput->writeln('<info>----------------</info>');

        // --------------------------------------------------------------------------

        //  Setup Factory - config files are required prior to set up
        Factory::setup();

        // --------------------------------------------------------------------------

        //  Check environment
        if (Environment::not(Environment::ENV_DEV)) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                'This tool is only available on DEVELOPMENT environments'
            );
        }

        return self::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Get a resource and substitute fields into it
     *
     * @param string $sFile   The file to fetch
     * @param array  $aFields The template fields
     *
     * @return string
     * @throws \Exception
     */
    protected function getResource(string $sFile, array $aFields): string
    {
        if (empty(static::RESOURCE_PATH)) {
            throw new \Exception('RESOURCE_PATH is not defined');
        }
        $sResource = require static::RESOURCE_PATH . $sFile;

        foreach ($aFields as $sField => $sValue) {
            $sKey      = '{{' . strtoupper($sField) . '}}';
            $sResource = str_replace($sKey, $sValue, $sResource);
        }

        return $sResource;
    }

    // --------------------------------------------------------------------------

    /**
     * Create (or replace) a file with some contents
     *
     * @param string $sPath     The file to create
     * @param string $sContents The contents to write
     *
     * @throws DoesNotExistException
     * @throws IsNotWritableException
     */
    protected function createFile(string $sPath, string $sContents = ''): void
    {
        $hHandle = fopen($sPath, 'w');
        if (!$hHandle) {
            throw new DoesNotExistException('Failed to open ' . $sPath . ' for writing');
        }

        if (fwrite($hHandle, $sContents) === false) {
            throw new IsNotWritableException('Failed to write to ' . $sPath);
        }

        fclose($hHandle);
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new path
     *
     * @param string $sPath The path to create
     *
     * @throws DoesNotExistException
     * @throws IsNotWritableException
     */
    protected function createPath(string $sPath): void
    {
        if (!is_dir($sPath)) {
            if (!@mkdir($sPath, self::FILE_PERMISSION, true)) {
                throw new DoesNotExistException('Path "' . $sPath . '" does not exist and could not be created');
            }
        }

        if (!is_writable($sPath)) {
            throw new IsNotWritableException('Path "' . $sPath . '" exists, but is not writable');
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Parses the arguments into an array ready for templates
     *
     * @return array
     */
    protected function getArguments(): array
    {
        Factory::helper('string');
        $aArguments = [];

        if (!empty($this->aArguments)) {
            foreach ($this->aArguments as $aArgument) {
                $aArguments[] = (object) [
                    'name'     => getFromArray('name', $aArgument),
                    'value'    => $this->oInput->getArgument(getFromArray('name', $aArgument)),
                    'required' => getFromArray('required', $aArgument),
                ];
            }
        } else {
            $aArgumentsRaw = array_slice($this->oInput->getArguments(), 1);
            foreach ($aArgumentsRaw as $sField => $sValue) {
                $sField       = strtoupper(camelcase_to_underscore($sField));
                $aArguments[] = (object) [
                    'name'     => $sField,
                    'value'    => $sValue,
                    'required' => true,
                ];
            }
        }

        unset($sField);
        unset($sValue);

        foreach ($aArguments as &$oArgument) {
            if (empty($oArgument->value)) {
                $sLabel = str_replace('_', ' ', $oArgument->name);
                $sLabel = ucwords(strtolower($sLabel));
                $sError = '';
                do {
                    $oArgument->value = $this->ask($sError . $sLabel . ':', '');
                    $sError           = '<error>Please specify</error> ';
                    if ($oArgument->required && empty($oArgument->value)) {
                        $bAskAgain = true;
                    } else {
                        $bAskAgain = false;
                    }
                } while ($bAskAgain);
            }
        }
        unset($oArgument);

        //  Finally set as key values
        $aOut = [];
        foreach ($aArguments as $oArgument) {
            $aOut[strtoupper($oArgument->name)] = $oArgument->value;
        }

        return $aOut;
    }
}
