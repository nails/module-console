<?php

namespace Nails\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class Base extends Command
{
    const EXIT_CODE_SUCCESS = 0;
    const EXIT_CODE_FAILURE = 1;

    // --------------------------------------------------------------------------

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $oInput;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $oOutput;

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param  InputInterface $oInput The Input Interface provided by Symfony
     * @param  OutputInterface $oOutput The Output Interface provided by Symfony
     * @return void
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        $this->oInput  = $oInput;
        $this->oOutput = $oOutput;
    }

    // --------------------------------------------------------------------------

    /**
     * Confirms something with the user
     *
     * @param  string $sQuestion The question to confirm
     * @param  boolean $bDefault The default answer
     * @return string
     */
    protected function confirm($sQuestion, $bDefault)
    {
        $sQuestion = is_array($sQuestion) ? implode("\n", $sQuestion) : $sQuestion;
        $oHelper   = $this->getHelper('question');
        $sDefault  = (bool) $bDefault ? 'Y' : 'N';
        $oQuestion = new ConfirmationQuestion($sQuestion . ' [' . $sDefault . ']: ', $bDefault);

        return $oHelper->ask($this->oInput, $this->oOutput, $oQuestion);
    }

    // --------------------------------------------------------------------------

    /**
     * Asks the user for some input
     *
     * @param  string $mQuestion The question to ask
     * @param  mixed $sDefault The default answer
     * @return string
     */
    protected function ask($mQuestion, $sDefault)
    {
        $mQuestion = is_array($mQuestion) ? implode("\n", $mQuestion) : $mQuestion;
        $oHelper   = $this->getHelper('question');
        $oQuestion = new Question($mQuestion . ' [' . $sDefault . ']: ', $sDefault);

        return $oHelper->ask($this->oInput, $this->oOutput, $oQuestion);
    }

    // --------------------------------------------------------------------------

    /**
     * Performs the abort functionality and returns the exit code
     *
     * @param  array $aMessages The error message
     * @param  integer $iExitCode The exit code
     * @return int
     */
    protected function abort($iExitCode = self::EXIT_CODE_FAILURE, $aMessages = [])
    {
        $aMessages = (array) $aMessages;
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
     * Call another command within the app
     *
     * @param string $sCommand The command to execute
     * @param array $aArguments Any arguments to pass to the command
     * @param bool $bInteractive Whether the command should be executed interactively
     * @param bool $bSilent Whether the command should be executed silently
     * @return int
     */
    protected function callCommand($sCommand, $aArguments = [], $bInteractive = true, $bSilent = false)
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
}
