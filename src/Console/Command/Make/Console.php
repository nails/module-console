<?php

namespace Nails\Console\Console\Command\Make;

use Nails\Console\Command\BaseMaker;
use Nails\Console\Exception\ConsoleException;
use Nails\Factory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Console extends BaseMaker
{
    const RESOURCE_PATH = __DIR__ . '/../../../../resources/console/';
    const APP_PATH      = NAILS_APP_PATH . 'src/Console/Command/';

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('make:console')
            ->setDescription('Creates a new App console command')
            ->addArgument(
                'commandName',
                InputArgument::OPTIONAL,
                'Define the name of the console command to create'
            );
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

        try {
            $this
                ->createPath(self::APP_PATH)
                ->createCommand();
        } catch (ConsoleException $e) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                [$e->getMessage()]
            );
        }

        // --------------------------------------------------------------------------

        //  Cleaning up
        $oOutput->writeln('');
        $oOutput->writeln('<comment>Cleaning up</comment>...');

        // --------------------------------------------------------------------------

        //  And we're done
        $oOutput->writeln('');
        $oOutput->writeln('Complete!');

        return self::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Create the Command
     *
     * @throws ConsoleException
     * @return $this
     */
    private function createCommand(): self
    {
        $aFields  = $this->getArguments();
        $aCreated = [];

        try {

            $aToCreate = [];
            $aCommands = array_filter(
                array_map(function ($sCommand) {
                    return implode('/', array_map('ucfirst', explode('/', ucfirst(trim($sCommand)))));
                }, explode(',', $aFields['COMMAND_NAME']))
            );

            sort($aCommands);

            foreach ($aCommands as $sCommand) {

                $aCommandBits = explode(':', $sCommand);
                $aCommandBits = array_map('ucfirst', $aCommandBits);

                $sNamespace       = $this->generateNamespace($aCommandBits);
                $sClassName       = $this->generateClassName($aCommandBits);
                $sClassNameFull   = $sNamespace . '\\' . $sClassName;
                $sClassNameNormal = str_replace('app:console:command:', '', strtolower(str_replace('\\', ':', $sClassNameFull)));
                $sFilePath        = $this->generateFilePath($aCommandBits);

                //  Test it does not already exist
                if (file_exists($sFilePath)) {
                    throw new ConsoleException(
                        'A command at "' . $sFilePath . '" already exists'
                    );
                }

                $aToCreate[] = [
                    'NAMESPACE'       => $sNamespace,
                    'CLASS_NAME'      => $sClassName,
                    'CLASS_NAME_FULL' => $sClassNameFull,
                    'COMMAND'         => $sClassNameNormal,
                    'FILE_PATH'       => $sFilePath,
                    'DIRECTORY'       => dirname($sFilePath) . DIRECTORY_SEPARATOR,
                ];
            }

            $this->oOutput->writeln('The following command(s) will be created:');
            foreach ($aToCreate as $aConfig) {
                $this->oOutput->writeln('');
                $this->oOutput->writeln('Class:   <info>' . $aConfig['CLASS_NAME_FULL'] . '</info>');
                $this->oOutput->writeln('Command: <info>' . $aConfig['COMMAND'] . '</info>');
                $this->oOutput->writeln('Path:    <info>' . $aConfig['FILE_PATH'] . '</info>');
            }
            $this->oOutput->writeln('');

            if ($this->confirm('Continue?', true)) {
                foreach ($aToCreate as $aConfig) {
                    $this->oOutput->writeln('');
                    $this->oOutput->write('Creating command <comment>' . $aConfig['COMMAND'] . '</comment>... ');
                    $this->createPath($aConfig['DIRECTORY']);
                    $this->createFile(
                        $aConfig['FILE_PATH'],
                        $this->getResource('template/console.php', $aConfig)
                    );
                    $aCreated[] = $aConfig['FILE_PATH'];
                    $this->oOutput->writeln('<info>done</info>');
                }
            }

        } catch (ConsoleException $e) {
            $this->oOutput->writeln('<error>fail</error>');
            //  Clean up created commands
            if (!empty($aCreated)) {
                $this->oOutput->writeln('<error>Cleaning up - removing newly created files</error>');
                foreach ($aCreated as $sPath) {
                    @unlink($sPath);
                }
            }
            throw new ConsoleException($e->getMessage());
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the class name
     *
     * @param array $aCommandBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateClassName(array $aCommandBits): string
    {
        return array_pop($aCommandBits);
    }

    // --------------------------------------------------------------------------


    /**
     * Generate the class namespace
     *
     * @param array $aCommandBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateNamespace(array $aCommandBits): string
    {
        array_pop($aCommandBits);
        return implode('\\', array_merge(['App', 'Console', 'Command'], $aCommandBits));
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the class file path
     *
     * @param array $aCommandBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateFilePath(array $aCommandBits): string
    {
        $sClassName = array_pop($aCommandBits);
        return implode(
            DIRECTORY_SEPARATOR,
            array_map(
                function ($sItem) {
                    return rtrim($sItem, DIRECTORY_SEPARATOR);
                },
                array_merge(
                    [static::APP_PATH],
                    $aCommandBits,
                    [$sClassName . '.php']
                )
            )
        );
    }
}
