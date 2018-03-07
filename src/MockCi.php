<?php

/**
 * Wow, this is a horrible, HORRIBLE approach.
 *
 * All this so we can avoid "MX_Lang" errors when database queries fail on the CLI.
 * If we could extend the DB classes then we'd have a much cleaner solution, but we
 * can't just now.
 *
 * Any thoughts on a nicer approach, please share.
 */

class CI
{
    public static $APP;
}

class MOCK_Config
{
    public function item()
    {
    }
}

class MOCK_Router
{
    public function fetch_module()
    {
    }
}

class MOCK_Load
{
    public function get_package_paths()
    {
    }
}

// --------------------------------------------------------------------------

global $CFG;
$CFG = new MOCK_Config();

CI::$APP = (object) [
    'config' => $CFG,
    'router' => new MOCK_Router(),
    'load'   => new MOCK_Load(),
];

// --------------------------------------------------------------------------

function get_instance()
{
    return CI::$APP;
}

// --------------------------------------------------------------------------

require_once FCPATH . BASEPATH . 'core/Lang.php';
require_once NAILS_COMMON_PATH . 'MX/Lang.php';
require_once NAILS_COMMON_PATH . 'MX/Modules.php';
