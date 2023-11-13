<?php
$this->functionHandlers['change_log'] = function ($args) {
    if ($this->ash->debug) echo ("debug: change_log()\n");
    $changes = $args["changes"] ?? [];
    $result = file_put_contents('/var/log/changelog', "[" . date('Y-m-d H:i:s') . "] $changes\n", FILE_APPEND);
    $result = ["bytes_written" => $result];
    return $result;
};