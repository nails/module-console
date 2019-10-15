<?php

/**
 * The class provides a summary of the events fired by this module
 *
 * @package     Nails
 * @subpackage  module-console
 * @category    Events
 * @author      Nails Dev Team
 */

namespace Nails\Console;

use Nails\Common\Events\Base;

/**
 * Class Events
 *
 * @package Nails\Console
 */
class Events extends Base
{
    /**
     * Fired when the Console starts
     */
    const STARTUP = 'STARTUP';

    /**
     * Fired when the Console is ready but before the command is executed
     */
    const READY = 'READY';

    /**
     * Fired before the command is called
     *
     * @param \Nails\Console\Command $oCommand The instance of the command being fired
     */
    const COMMAND_PRE = 'COMMAND:PRE';

    /**
     * Fired after the command is called
     *
     * @param \Nails\Console\Command $oCommand  The instance of the command being fired
     * @param int                    $iExitCode The exit code which the command generated
     */
    const COMMAND_POST = 'COMMAND:POST';

    /**
     * Fired when the Console ends
     */
    const SHUTDOWN = 'SHUTDOWN';
}
