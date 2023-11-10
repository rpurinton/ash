#!/usr/local/lib/php -f
<?php

namespace Rpurinton\Ash;

$uptime = shell_exec("uptime");
$host_fqdn = shell_exec("hostname");
$host_name = shell_exec("hostname -s");
$user_id = shell_exec("whoami");
$working_dir = shell_exec("pwd");
$working_folder = basename($working_dir);
$prompt = "(ash) [$user_id@$host_name $working_folder]# ";

echo ("Hi I'm Ashly! I'm an AI linux shell.\nPress CTRL+C or Type 'exit' to quit.\n\n");

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
