<?php

/**
 * This file is the template for the contents of emails
 * Used by the console command when creating emails.
 */

return <<<'EOD'
<?php

/**
 * The {{COMMAND}} console command
 *
 * @package  App
 * @category Console
 */

namespace {{NAMESPACE}};

use Nails\Console\Command\Base;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class {{CLASS_NAME}} extends Base
{
    /**
     * Configure the {{COMMAND}} command
     */
    protected function configure()
    {
        $this
            ->setName('{{COMMAND}}')
            ->setDescription('@todo - describe what this command does');
    }

    // --------------------------------------------------------------------------

    /**
     * Execute the command
     *
     * @param InputInterface  $oInput  The Input Interface provided by Symfony
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        parent::execute($oInput, $oOutput);

        $this->oOutput->writeln('The <info>{{COMMAND}}</info> console command');

        return static::EXIT_CODE_SUCCESS;
    }
}

EOD;
