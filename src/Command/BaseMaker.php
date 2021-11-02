<?php

namespace Nails\Console\Command;

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Helper\ArrayHelper;
use Nails\Common\Service\FileCache;
use Nails\Console\Exception\ConsoleException;
use Nails\Console\Exception\Path\DoesNotExistException;
use Nails\Console\Exception\Path\IsNotWritableException;
use Nails\Environment;
use Nails\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class BaseMaker
 *
 * @package Nails\Console\Command
 */
abstract class BaseMaker extends Base
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

    /**
     * The number of spaces which comprise a tab
     *
     * @var int
     */
    const TAB_WIDTH = 4;

    /**
     * The path to the services file
     *
     * @var string
     */
    const SERVICE_PATH = NAILS_APP_PATH . 'application/services/services.php';

    /**
     * The name of the temporary service file (used while generating)
     *
     * @var string
     */
    const SERVICE_TEMP_NAME = 'services.temp.php';

    /**
     * The name of the token in the service file
     *
     * @var string
     */
    const SERVICE_TOKEN = '';

    // --------------------------------------------------------------------------

    /**
     * The passed arguments
     *
     * @var array
     */
    protected $aArguments = [];

    /**
     * The resource created by fopen()
     */
    protected $fServicesHandle;

    /**
     * The location of the token
     *
     * @var int
     */
    protected $iServicesTokenLocation;

    /**
     * The indent of the token
     *
     * @var int
     */
    protected $iServicesIndent;

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        parent::configure();
        foreach ($this->aArguments as $aArgument) {
            $this->addArgument(
                ArrayHelper::get('name', $aArgument),
                ArrayHelper::get('mode', $aArgument, InputArgument::OPTIONAL),
                ArrayHelper::get('description', $aArgument),
                ArrayHelper::get('default', $aArgument)
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param InputInterface  $oInput  The Input Interface provided by Symfony
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        $this->banner('Nails Maker Tool');

        // --------------------------------------------------------------------------

        //  Check environment
        if (Environment::not(Environment::ENV_DEV)) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                ['This tool is only available on ' . Environment::ENV_DEV . ' environments']
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
     * @throws NailsException
     */
    protected function getResource(string $sFile, array $aFields): string
    {
        if (empty(static::RESOURCE_PATH)) {
            throw new NailsException('RESOURCE_PATH is not defined');
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
     * @return $this
     * @throws DoesNotExistException
     * @throws IsNotWritableException
     */
    protected function createFile(string $sPath, string $sContents = ''): BaseMaker
    {
        $hHandle = fopen($sPath, 'w');
        if (!$hHandle) {
            throw new DoesNotExistException('Failed to open ' . $sPath . ' for writing');
        }

        if (fwrite($hHandle, $sContents) === false) {
            throw new IsNotWritableException('Failed to write to ' . $sPath);
        }

        fclose($hHandle);

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Creates a new path
     *
     * @param string $sPath The path to create
     *
     * @return $this
     * @throws DoesNotExistException
     * @throws IsNotWritableException
     */
    protected function createPath(string $sPath): BaseMaker
    {
        if (!is_dir($sPath)) {
            if (!@mkdir($sPath, self::FILE_PERMISSION, true)) {
                throw new DoesNotExistException('Path "' . $sPath . '" does not exist and could not be created');
            }
        }

        if (!is_writable($sPath)) {
            throw new IsNotWritableException('Path "' . $sPath . '" exists, but is not writable');
        }

        return $this;
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
                    'name'       => ArrayHelper::get('name', $aArgument),
                    'value'      => $this->oInput->getArgument(ArrayHelper::get('name', $aArgument)),
                    'required'   => ArrayHelper::get('required', $aArgument),
                    'validation' => ArrayHelper::get('validation', $aArgument),
                ];
            }
        } else {
            $aArgumentsRaw = array_slice($this->oInput->getArguments(), 1);
            foreach ($aArgumentsRaw as $sField => $sValue) {
                $sField       = strtoupper(camelcase_to_underscore($sField));
                $aArguments[] = (object) [
                    'name'       => $sField,
                    'value'      => $sValue,
                    'required'   => true,
                    'validation' => null,
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
                    $oArgument->value = $this->ask($sError . PHP_EOL . $sLabel . ':', '');
                    if ($oArgument->required && empty($oArgument->value)) {
                        $sError    = '<error>Please specify</error> ';
                        $bAskAgain = true;
                    } elseif (is_callable($oArgument->validation)) {
                        try {
                            $cFunction = $oArgument->validation;
                            $cFunction($oArgument->value);
                            $bAskAgain = false;
                        } catch (ValidationException $e) {
                            $sError    = '<error>' . $e->getMessage() . '</error> ';
                            $bAskAgain = true;
                        }
                    } else {
                        $bAskAgain = false;
                    }
                } while ($bAskAgain);
            } elseif (is_callable($oArgument->validation)) {
                $cFunction = $oArgument->validation;
                $cFunction($oArgument->value);
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

    // --------------------------------------------------------------------------

    /**
     * Generates N number of tabs
     *
     * @param int $iNumberTabs The number of tabs to generate
     *
     * @return string
     */
    protected function tabs($iNumberTabs = 0): string
    {
        return str_repeat(' ', static::TAB_WIDTH * $iNumberTabs);
    }

    // --------------------------------------------------------------------------

    /**
     * Validate the service file is valid
     *
     * @return $this
     * @throws ConsoleException
     */
    protected function validateServiceFile(string $sToken = null): BaseMaker
    {
        if (empty($sToken) && empty(static::SERVICE_TOKEN)) {
            throw new ConsoleException(
                'SERVICE_TOKEN is not set'
            );
        } elseif (empty($sToken)) {
            $sToken = static::SERVICE_TOKEN;
        }

        //  Detect the services file
        if (!file_exists(static::SERVICE_PATH)) {
            throw new ConsoleException(
                'Could not detect the app\'s services.php file: ' . static::SERVICE_PATH
            );
        }

        //  Look for the generator token
        $this->fServicesHandle = fopen(static::SERVICE_PATH, 'r+');
        $bFound                = false;
        if ($this->fServicesHandle) {
            $iLocation = 0;
            while (($sLine = fgets($this->fServicesHandle)) !== false) {
                if (preg_match('#^(\s*)// GENERATOR\[' . $sToken . ']#', $sLine, $aMatches)) {
                    $bFound                       = true;
                    $this->iServicesIndent        = strlen($aMatches[1]);
                    $this->iServicesTokenLocation = $iLocation;
                    break;
                }
                $iLocation = ftell($this->fServicesHandle);
            }
            if (!$bFound) {
                fclose($this->fServicesHandle);
                throw new ConsoleException(
                    'Services file does not contain the generator token (i.e // GENERATOR[' . $sToken . ']) ' .
                    'This token is required so that the tool can safely insert new definitions'
                );
            }
        } else {
            throw new ConsoleException(
                'Failed to open the services file for reading and writing: ' . static::SERVICE_PATH
            );
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Write the definitions to the services file
     *
     * @param array $aServiceDefinitions The definitions to write
     *
     * @return $this
     * @throws FactoryException
     */
    protected function writeServiceFile(array $aServiceDefinitions = []): BaseMaker
    {
        /** @var FileCache $oFileCache */
        $oFileCache  = Factory::service('FileCache');
        $sTempFile   = $oFileCache->getDir() . static::SERVICE_TEMP_NAME;
        $fTempHandle = fopen($sTempFile, 'w+');
        rewind($this->fServicesHandle);
        $iLocation = 0;
        while (($sLine = fgets($this->fServicesHandle)) !== false) {
            if ($iLocation === $this->iServicesTokenLocation) {
                fwrite(
                    $fTempHandle,
                    implode(PHP_EOL, $aServiceDefinitions) . PHP_EOL
                );
            }
            fwrite($fTempHandle, $sLine);
            $iLocation = ftell($this->fServicesHandle);
        }

        $aFile      = [];
        $aServices  = [];
        $aModels    = [];
        $aFactories = [];
        $aResources = [];

        $aArray = &$aFile;

        rewind($fTempHandle);
        while (($sLine = fgets($fTempHandle)) !== false) {

            if (preg_match('/^' . $this->tabs(1) . '\'services\'/', $sLine)) {
                $aArray[] = $sLine;
                $aArray[] = 'SERVICES';
                $aArray   = &$aServices;
                continue;

            } elseif (preg_match('/^' . $this->tabs(1) . '\'models\'/', $sLine)) {
                $aArray[] = $sLine;
                $aArray[] = 'MODELS';
                $aArray   = &$aModels;
                continue;

            } elseif (preg_match('/^' . $this->tabs(1) . '\'factories\'/', $sLine)) {
                $aArray[] = $sLine;
                $aArray[] = 'FACTORIES';
                $aArray   = &$aFactories;
                continue;

            } elseif (preg_match('/^' . $this->tabs(1) . '\'resources\'/', $sLine)) {
                $aArray[] = $sLine;
                $aArray[] = 'RESOURCES';
                $aArray   = &$aResources;
                continue;

            } elseif (preg_match('/^' . $this->tabs(1) . '\],/', $sLine)) {
                $aArray = &$aFile;
            }

            if (!preg_match('/' . $this->tabs(2) . '\/\/ GENERATOR\[.*\]/', $sLine)) {
                $aArray[] = $sLine;
            }
        }

        fclose($fTempHandle);

        $aMap = [
            [$aServices, 'SERVICES'],
            [$aModels, 'MODELS'],
            [$aFactories, 'FACTORIES'],
            [$aResources, 'RESOURCES'],
        ];

        foreach ($aMap as $aConfig) {

            [$aLines, $sToken] = $aConfig;

            $aSections   = [];
            $sIdentifier = null;

            foreach ($aLines as $sLine) {

                if (preg_match('/^' . $this->tabs(2) . '\'(.*?)\' *=> *function/', $sLine, $aMatches)) {
                    $sIdentifier = $aMatches[1];
                }

                if (!array_key_exists($sIdentifier, $aSections)) {
                    $aSections[$sIdentifier] = '';
                }

                $aSections[$sIdentifier] .= $sLine;
            }

            ksort($aSections);

            $aSections[] = $this->tabs(2) . '// GENERATOR[' . $sToken . ']' . PHP_EOL;

            $sSections = implode('', $aSections);
            array_splice($aFile, array_search($sToken, $aFile), 1, $aSections);
        }

        $fTempHandle = fopen($sTempFile, 'w+');
        fwrite($fTempHandle, implode('', $aFile));

        //  Move the temp services file into place
        unlink(static::SERVICE_PATH);
        rename($sTempFile, static::SERVICE_PATH);
        fclose($fTempHandle);
        fclose($this->fServicesHandle);

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Takes a string of deliminated classnames and parse into valid classes
     *
     * @param string $sClassNames The string to parse
     * @param bool   $bSort       Whether to sort the results
     *
     * @return string[]
     */
    protected function parseClassNames(string $sClassNames, bool $bSort = true): array
    {
        $aClasses = array_filter(
            array_map(
                function ($sClass) {

                    $aBits = preg_split('(:|\\\|\/)', trim($sClass));
                    $aBits = array_map('ucfirst', $aBits);
                    $aBits = implode('/', $aBits);

                    return $aBits;

                },
                preg_split('/(,|;| )/', $sClassNames)
            )
        );

        if ($bSort) {
            sort($aClasses);
        }

        return $aClasses;
    }
}
