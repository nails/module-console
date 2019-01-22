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

require NAILS_CI_SYSTEM_PATH . 'core/Utf8.php';

class Utf8 extends \CI_Utf8
{
    public function __construct()
    {
        static::defineCharsetConstants();
        parent::__construct();
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
            define('MB_ENABLED', true);
            @ini_set('mbstring.internal_encoding', $sCharset);
            mb_substitute_character('none');
        } else {
            define('MB_ENABLED', false);
        }

        if (extension_loaded('iconv')) {
            define('ICONV_ENABLED', true);
            @ini_set('iconv.internal_encoding', $sCharset);
        } else {
            define('ICONV_ENABLED', false);
        }

        if (is_php('5.6')) {
            ini_set('php.internal_encoding', $sCharset);
        }
    }
}
