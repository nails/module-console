<?php

namespace Nails\Console\Command;

use Nails\Console\Exception\Path\DoesNotExistException;
use Nails\Console\Exception\Path\IsNotWritableException;
use Nails\Environment;
use Nails\Factory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseMaker extends Base
{
    /**
     * The permission to write files with
     */
    const FILE_PERMISSION = 0755;

    /**
     * Where resources are stored
     */
    const RESOURCE_PATH = '';

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

        $oOutput->writeln('');
        $oOutput->writeln('<info>----------------</info>');
        $oOutput->writeln('<info>Nails Maker Tool</info>');
        $oOutput->writeln('<info>----------------</info>');

        // --------------------------------------------------------------------------

        //  Setup Factory - config files are required prior to set up
        Factory::setup();

        // --------------------------------------------------------------------------

        //  Check environment
        if (Environment::not('DEVELOPMENT')) {
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
     * @param string $sFile The file to fetch
     * @param array $aFields The template fields
     * @return string
     * @throws \Exception
     */
    protected function getResource($sFile, $aFields)
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
     * @param string $sPath The file to create
     * @param string $sContents The contents to write
     * @throws DoesNotExistException
     * @throws IsNotWritableException
     */
    protected function createFile($sPath, $sContents = '')
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
     * @throws DoesNotExistException
     * @throws IsNotWritableException
     */
    protected function createPath($sPath)
    {
        if (!is_dir($sPath)) {
            if (!mkdir($sPath, self::FILE_PERMISSION, true)) {
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
    protected function getArguments()
    {
        Factory::helper('string');
        $aArgumentsRaw = array_slice($this->oInput->getArguments(), 1);
        $aArguments    = [];
        foreach ($aArgumentsRaw as $sField => $sValue) {
            $sField = strtoupper(camelcase_to_underscore($sField));
            $aArguments[$sField] = $sValue;
        }
        unset($sField);
        unset($sValue);

        foreach ($aArguments as $sField => &$sValue) {
            if (empty($sValue)) {
                $sLabel = str_replace('_', ' ', $sField);
                $sLabel = ucwords(strtolower($sLabel));
                $sError = '';
                do {
                    $sValue = $this->ask($sError . $sLabel . ':', '');
                    $sError = '<error>Please specify</error> ';
                } while (empty($sValue));
            }
        }
        unset($sValue);

        return $aArguments;
    }
}
