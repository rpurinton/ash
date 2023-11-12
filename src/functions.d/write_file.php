<?php
$this->functionHandlers['write_file'] = function ($args) {
    if ($this->ash->debug) echo ("debug: write_file(" . $args["path"] . ")\n");
    $path = $args['path'] ?? "";
    $content = $args['content'] ?? "";
    $append = $args['append'] ?? false;
    $owner = $args['owner'] ?? "";
    $group = $args['group'] ?? "";
    $chmod = $args['chmod'] ?? "";
    echo ("$path\n");
    if ($append) $result = file_put_contents($path, $content, FILE_APPEND);
    else $result = file_put_contents($path, $content);
    if (($owner != "") && ($group != "")) {
        shell_exec("chown $owner:$group $path");
    }
    if ($chmod != "") {
        shell_exec("chmod $chmod $path");
    }
    if ($this->ash->debug) echo ("debug: write_file() bytes written: $result\n");
    $result = ["bytes_written" => $result];
    return $result;
};
