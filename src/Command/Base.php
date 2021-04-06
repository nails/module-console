<?php

namespace Nails\Console\Command;

use Exception;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use Nails\Common\Exception\ValidationException;
use Nails\Common\Service\Event;
use Nails\Console\Events;
use Nails\Factory;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Allow the app to add functionality, if needed
 */
if (class_exists('\App\Console\Command\Base')) {
    abstract class BaseMiddle extends \App\Console\Command\Base
    {
        public function __construct()
        {
            if (!classExtends(parent::class, Command::class)) {
                throw new NailsException(sprintf(
                    'Class %s must extend %s',
                    parent::class,
                    Command::class
                ));
            }
            parent::__construct();
        }
    }
} else {
    abstract class BaseMiddle extends Command
    {
    }
}

// --------------------------------------------------------------------------

/**
 * Class Base
 *
 * @package Nails\Console\Command
 */
class Base extends BaseMiddle
{
    /**
     * Exit code statuses
     *
     * @var int
     */
    const EXIT_CODE_SUCCESS = 0;
    const EXIT_CODE_FAILURE = 1;

    // --------------------------------------------------------------------------

    /**
     * @var InputInterface
     */
    protected $oInput;

    /**
     * @var OutputInterface
     */
    protected $oOutput;

    // --------------------------------------------------------------------------

    /**
     * Runs the app, overridden to add Nails events
     *
     * @param InputInterface  $oInput
     * @param OutputInterface $oOutput
     *
     * @return int
     * @throws FactoryException
     * @throws NailsException
     * @throws ReflectionException
     */
    public function run(InputInterface $oInput, OutputInterface $oOutput)
    {
        /** @var Event $oEventService */
        $oEventService = Factory::service('Event');
        $oEventService
            ->trigger(Events::COMMAND_PRE, Events::getEventNamespace(), [$this]);

        $iExitCode = parent::run($oInput, $oOutput);

        $oEventService
            ->trigger(Events::COMMAND_POST, Events::getEventNamespace(), [$this, $iExitCode]);

        return $iExitCode;
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
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        $this->oInput  = $oInput;
        $this->oOutput = $oOutput;
        $this->setStyles($oOutput);
        return static::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Adds additional styles to the output
     *
     * @param OutputInterface $oOutput The output interface
     */
    public function setStyles(OutputInterface $oOutput): void
    {
        $oWarningStyle = new OutputFormatterStyle('white', 'yellow');
        $oOutput->getFormatter()->setStyle('warning', $oWarningStyle);
    }

    // --------------------------------------------------------------------------

    /**
     * Confirms something with the user
     *
     * @param string  $sQuestion The question to confirm
     * @param boolean $bDefault  The default answer
     *
     * @return string
     */
    protected function confirm($sQuestion, $bDefault): bool
    {
        $sQuestion = is_array($sQuestion) ? implode("\n", $sQuestion) : $sQuestion;
        $oHelper   = $this->getHelper('question');
        $sDefault  = (bool) $bDefault ? 'Y' : 'N';
        $oQuestion = new ConfirmationQuestion($sQuestion . ' [' . $sDefault . ']: ', $bDefault);

        return (bool) $oHelper->ask($this->oInput, $this->oOutput, $oQuestion);
    }

    // --------------------------------------------------------------------------

    /**
     * Asks the user for some input
     *
     * @param string $mQuestion The question to ask
     * @param mixed  $sDefault  The default answer
     *
     * @return string
     */
    protected function ask($mQuestion, $sDefault, bool $bHideAnswer = false)
    {
        $mQuestion = is_array($mQuestion) ? implode("\n", $mQuestion) : $mQuestion;
        $oHelper   = $this->getHelper('question');
        $oQuestion = new Question($mQuestion . ' [' . $sDefault . ']: ', $sDefault);
        $oQuestion->setHidden($bHideAnswer);

        return $oHelper->ask($this->oInput, $this->oOutput, $oQuestion);
    }

    // --------------------------------------------------------------------------

    /**
     * Performs the abort functionality and returns the exit code
     *
     * @param integer $iExitCode The exit code
     * @param array   $aMessages The error message
     *
     * @return int
     */
    protected function abort($iExitCode = self::EXIT_CODE_FAILURE, array $aMessages = [])
    {
        if ($iExitCode === self::EXIT_CODE_FAILURE) {
            $aMessages  = array_merge(['AN ERROR OCCURRED:', ''], $aMessages);
            $iPadSize   = 2;
            $iMaxLength = max(array_map('strlen', $aMessages)) + ($iPadSize * 2);
        } else {
            $iPadSize   = 0;
            $iMaxLength = 0;
        }

        $sColorOpen  = $iExitCode === self::EXIT_CODE_FAILURE ? '<error>' : '';
        $sColorClose = $iExitCode === self::EXIT_CODE_FAILURE ? '</error>' : '';

        $this->oOutput->writeln('');
        $this->oOutput->writeln($sColorOpen . str_repeat(' ', $iMaxLength) . $sColorClose);
        foreach ($aMessages as $sLine) {
            $this->oOutput->writeln(
                $sColorOpen .
                str_repeat(' ', $iPadSize) .
                str_pad($sLine, $iMaxLength - ($iPadSize * 2)) .
                str_repeat(' ', $iPadSize) .
                $sColorClose
            );
        }
        $this->oOutput->writeln($sColorOpen . str_repeat(' ', $iMaxLength) . $sColorClose);
        $this->oOutput->writeln('');

        return $iExitCode;
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an error block
     *
     * @param array $aLines The lines to render
     */
    protected function error(array $aLines): void
    {
        $this->outputBlock($aLines, 'error');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an warning block
     *
     * @param array $aLines The lines to render
     */
    protected function warning(array $aLines): void
    {
        $this->outputBlock($aLines, 'warning');
    }

    // --------------------------------------------------------------------------

    /**
     * Renders an coloured block
     *
     * @param array  $aLines The lines to render
     * @param string $sType  The type of block to render
     */
    protected function outputBlock(array $aLines, string $sType): void
    {
        $aLengths   = array_map('strlen', $aLines);
        $iMaxLength = max($aLengths);

        $this->oOutput->writeln('<' . $sType . '> ' . str_pad('', $iMaxLength, ' ') . ' </' . $sType . '>');
        foreach ($aLines as $sLine) {
            $this->oOutput->writeln('<' . $sType . '> ' . str_pad($sLine, $iMaxLength, ' ') . ' </' . $sType . '>');
        }
        $this->oOutput->writeln('<' . $sType . '> ' . str_pad('', $iMaxLength, ' ') . ' </' . $sType . '>');
    }

    // --------------------------------------------------------------------------

    /**
     * Call another command within the app
     *
     * @param string $sCommand     The command to execute
     * @param array  $aArguments   Any arguments to pass to the command
     * @param bool   $bInteractive Whether the command should be executed interactively
     * @param bool   $bSilent      Whether the command should be executed silently
     *
     * @return int
     * @throws Exception
     */
    protected function callCommand($sCommand, array $aArguments = [], $bInteractive = true, $bSilent = false)
    {
        $oCmd       = $this->getApplication()->find($sCommand);
        $aArguments = array_merge(['command' => $sCommand], $aArguments);
        $oCmdInput  = new ArrayInput($aArguments);

        $oCmdInput->setInteractive($bInteractive);

        if ($bSilent) {
            $oCmdOutput = new NullOutput();
        } else {
            $oCmdOutput = $this->oOutput;
        }

        return $oCmd->run($oCmdInput, $oCmdOutput);
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a command exists
     *
     * @param string $sCommand The command to test
     *
     * @return bool
     */
    protected function isCommand(string $sCommand)
    {
        try {
            $this->getApplication()->find($sCommand);
            return true;
        } catch (CommandNotFoundException $e) {
            return false;
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Outputs a banner
     *
     * @param string $sText The text for the banner
     * @param string $sType The type of banner
     */
    protected function banner(string $sText, string $sType = 'info')
    {
        $sText = trim($sText);
        $this->oOutput->writeln('');
        $this->oOutput->writeln('<' . $sType . '>' . str_repeat('-', mb_strlen($sText)) . '</' . $sType . '>');
        $this->oOutput->writeln('<' . $sType . '>' . $sText . '</' . $sType . '>');
        $this->oOutput->writeln('<' . $sType . '>' . str_repeat('-', mb_strlen($sText)) . '</' . $sType . '>');
        $this->oOutput->writeln('');
    }

    // --------------------------------------------------------------------------

    /**
     * Display a neatly aligned key-value listing
     *
     * @param array $aKeyValuePairs
     *
     * @return $this
     */
    protected function keyValueList(
        array $aKeyValuePairs,
        bool $bPadTop = true,
        bool $bPadBottom = true,
        string $sSeparator = ':'
    ): Command {

        if (empty($aKeyValuePairs)) {
            return $this;
        }

        $aKeys    = array_keys($aKeyValuePairs);
        $aValues  = array_values($aKeyValuePairs);
        $iKeysMax = max(array_map('strlen', $aKeys)) + 1;

        $aKeys = array_map(function ($sKey) use ($iKeysMax, $sSeparator) {
            return '<comment>' . $sKey . '</comment>' . $sSeparator . str_repeat(' ', $iKeysMax - strlen($sKey));
        }, $aKeys);

        $aKeyValuePairs = array_combine($aKeys, $aValues);

        if ($bPadTop) {
            $this->oOutput->writeln('');
        }
        foreach ($aKeyValuePairs as $sKey => $sValue) {
            $this->oOutput->writeln($sKey . $sValue);
        }
        if ($bPadBottom) {
            $this->oOutput->writeln('');
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Validates that a string is a valid class name
     *
     * @param string $sClassName The string to validate
     */
    protected function validateClassName(string $sClassName)
    {
        $sClassName = str_replace('/', '\\', $sClassName);
        $aSegments  = explode('\\', $sClassName);

        foreach ($aSegments as $sSegment) {
            if (!preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $sSegment)) {
                throw new ValidationException(
                    implode("\n", [
                        'Invalid class name',
                        str_repeat(' ', strpos($sClassName, $sSegment)) . '↓',
                        $sClassName,
                        str_repeat(' ', strpos($sClassName, $sSegment)) . '↑',
                    ])
                );
            }
        }
    }
}
