<?php

namespace Rpurinton\Ash;

class Composer
{
    public function install_dependencies($debug = false)
    {
        echo "(ash) Installing dependencies...";
        $cmd = "cd " . __DIR__ . " && export COMPOSER_ALLOW_SUPERUSER=1 && export COMPOSER_NO_INTERACTION=1 && composer install 2>&1";
        exec($cmd, $output, $exit_code);
        if ($debug) echo "(ash) install_dependencies() result: " . print_r($output, true) . "\n";
        if ($exit_code != 0) {
            echo "failed.\n";
            echo "(ash) Error: composer install failed.\n";
            exit(1);
        }
        echo "done.\n";
    }
}
