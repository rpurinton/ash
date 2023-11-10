#!/usr/local/bin/php -f
<?php

namespace Rpurinton\ash;

$uptime = trim(shell_exec("uptime"));
$host_fqdn = trim(shell_exec("hostname"));
$host_name = trim(shell_exec("hostname -s"));
$user_id = trim(shell_exec("whoami"));
$working_dir = trim(shell_exec("pwd"));
$working_folder = basename($working_dir);
$working_folder = $working_folder == "" ? "/" : $working_folder;
$prompt = "ash# ";

while (true) {
    $input = readline($prompt);
    readline_add_history($input);
    $input = trim($input);
    if ($input == "exit") {
        break;
    }
    $output = shell_exec($input);
    echo $output;
}
