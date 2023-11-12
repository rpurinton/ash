<?php

namespace Rpurinton\Ash;

class Composer
{
    public function install_dependencies($debug = false)
    {
        echo "Installing dependencies...";
        $composer = trim(shell_exec("which composer"));
        $error_start = "/usr/bin/which: no composer in";
        if (substr($composer, 0, strlen($error_start)) == $error_start) {
            echo "composer not found, installing locally...";
            $cmd = "cd " . __DIR__ . " && curl -sS https://getcomposer.org/installer | php && mv composer.phar composer && chmod +x composer";
            exec($cmd, $output, $exit_code);
            if ($debug) echo "install_dependencies() result: " . print_r($output, true) . "\n";
            if ($exit_code != 0) {
                echo "failed.\n";
                echo "Error: composer install failed.\n";
                exit(1);
            }
            echo "done.\n";
            $cmd = "cd " . __DIR__ . " && export COMPOSER_ALLOW_SUPERUSER=1 && export COMPOSER_NO_INTERACTION=1 && ./composer install 2>&1";
            exec($cmd, $output, $exit_code);
            if ($debug) echo "install_dependencies() result: " . print_r($output, true) . "\n";
            if ($exit_code != 0) {
                echo "failed.\n";
                echo "Error: composer install failed.\n";
                exit(1);
            }
            echo "done.\n";
            return;
        }
        $cmd = "cd " . __DIR__ . " && export COMPOSER_ALLOW_SUPERUSER=1 && export COMPOSER_NO_INTERACTION=1 && $composer install 2>&1";
        exec($cmd, $output, $exit_code);
        if ($debug) echo "install_dependencies() result: " . print_r($output, true) . "\n";
        if ($exit_code != 0) {
            echo "failed.\n";
            echo "Error: composer install failed.\n";
            exit(1);
        }
        echo "done.\n";
    }
}
