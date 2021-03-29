<?php

/**
 * Loads CodeIgniter's console class, and defines some constants along the way
 *
 * @package     Nails
 * @subpackage  common
 * @category    core
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Console;

use CI_Utf8;
use Nails\Functions;

require_once NAILS_CI_SYSTEM_PATH . 'core/Utf8.php';

/**
 * Class Utf8
 *
 * @package Nails\Console
 */
class Utf8 extends CI_Utf8
{
    /**
     * Whether the class has been instantiated before.
     *
     * If this class is instantiated multiple times then calling the
     * constructor will attempt to define constants again. The constructors
     * only sets constants so no need to run it if it's already been run once.
     *
     * @var bool
     */
    protected static $INITIALISED = false;

    // --------------------------------------------------------------------------

    /**
     * Utf8 constructor.
     */
    public function __construct()
    {
        if (!static::$INITIALISED) {
            static::$INITIALISED = true;
            static::defineCharsetConstants();
            parent::__construct();
        }
    }

    // --------------------------------------------------------------------------

    /**
     * This method defines some items which are declared by CodeIgniter
     */
    protected static function defineCharsetConstants()
    {
        /**
         * See vendor/codeigniter/framework/system/core/CodeIgniter.php for
         * details of what/why this is happening.
         */

        $sCharset = strtoupper(config_item('charset'));
        ini_set('default_charset', $sCharset);

        if (extension_loaded('mbstring')) {
            Functions::define('MB_ENABLED', true);
            @ini_set('mbstring.internal_encoding', $sCharset);
            mb_substitute_character('none');

        } else {
            Functions::define('MB_ENABLED', false);
        }

        if (extension_loaded('iconv')) {
            Functions::define('ICONV_ENABLED', true);
            @ini_set('iconv.internal_encoding', $sCharset);

        } else {
            Functions::define('ICONV_ENABLED', false);
        }
    }
}
