<?php

return array(
    'services' => array(
        'ConsoleDatabase' => function () {
            //  @todo: Use the common Database driver once it is not dependant on CI
            return new \Nails\Console\Database();
        }
    )
);
