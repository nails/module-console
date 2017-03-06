<?php

return array(
    'services' => array(
        'Database' => function () {
            //  @todo - Use the common Database driver once it is not dependant on CI
            if (class_exists('\App\Console\Database')) {
                return new \App\Console\Database();
            } else {
                return new \Nails\Console\Database();
            }
        },
        'ConsoleDatabase' => function () {
            //  Alias of above
            if (class_exists('\App\Console\Database')) {
                return new \App\Console\Database();
            } else {
                return new \Nails\Console\Database();
            }
        }
    )
);
