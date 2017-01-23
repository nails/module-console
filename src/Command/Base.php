<?php

namespace Nails\Console\Command;

use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Command\Command;

class Base extends Command
{
    const EXIT_CODE_SUCCESS = 0;
    const EXIT_CODE_FAILURE = 1;

    // --------------------------------------------------------------------------

    /**
     * Confirms something with the user
     * @param  string          $question The question to confirm
     * @param  boolean         $default  The default answer
     * @param  InputInterface  $input    The Input Interface proivided by Symfony
     * @param  OutputInterface $output   The Output Interface proivided by Symfony
     * @return string
     */
    protected function confirm($question, $default, $input, $output)
    {
        $question      = is_array($question) ? implode("\n", $question) : $question;
        $helper        = $this->getHelper('question');
        $defaultString = $default ? 'Y' : 'N';
        $question      = new ConfirmationQuestion($question . ' [' . $defaultString . ']: ', $default) ;

        return $helper->ask($input, $output, $question);
    }

    // --------------------------------------------------------------------------

    /**
     * Asks the user for some input
     * @param  string          $question The question to ask
     * @param  mixed           $default  The default answer
     * @param  InputInterface  $input    The Input Interface proivided by Symfony
     * @param  OutputInterface $output   The Output Interface proivided by Symfony
     * @return string
     */
    protected function ask($question, $default, $input, $output)
    {
        $question = is_array($question) ? implode("\n", $question) : $question;
        $helper   = $this->getHelper('question');
        $question = new Question($question . ' [' . $default . ']: ', $default) ;

        return $helper->ask($input, $output, $question);
    }

    // --------------------------------------------------------------------------

    /**
     * Performs the abort functionality and returns the exit code
     *
     * @param  OutputInterface $oOutput The Output Interface provided by Symfony
     * @param  array $aMessages The error message
     * @param  integer $iExitCode The exit code
     * @return int
     */
    protected function abort($oOutput, $iExitCode = self::EXIT_CODE_FAILURE, $aMessages = [])
    {
        $aMessages   = (array) $aMessages;
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

        $oOutput->writeln('');
        $oOutput->writeln($sColorOpen . str_repeat(' ', $iMaxLength) . $sColorClose);
        foreach ($aMessages as $sLine) {
            $oOutput->writeln(
                $sColorOpen .
                str_repeat(' ', $iPadSize) .
                str_pad($sLine, $iMaxLength - ($iPadSize * 2)) .
                str_repeat(' ', $iPadSize) .
                $sColorClose
            );
        }
        $oOutput->writeln($sColorOpen . str_repeat(' ', $iMaxLength) . $sColorClose);
        $oOutput->writeln('');

        return $iExitCode;
    }
}
