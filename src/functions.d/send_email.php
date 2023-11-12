<?php
$this->functionHandlers['send_email'] = function ($args) {
    if ($this->ash->debug) echo ("debug: sendemail(" . print_r($args, true) . ")\n");

    // Extract arguments
    $from = $args['from'] ?? "";
    $to = $args['to'] ?? "";
    $cc = $args['cc'] ?? "";
    $bcc = $args['bcc'] ?? "";
    $subject = $args['subject'] ?? "";
    $body = $args['body'] ?? "";

    // Basic validation
    if (empty($from) || empty($to) || empty($subject) || empty($body)) {
        $error = "Error (ash): Missing required fields.";
        if ($this->ash->debug) echo ("debug: send_email() error: $error\n");
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }

    // Prepare headers
    $headers = "From: $from\r\n";
    if (!empty($cc)) {
        $headers .= "Cc: $cc\r\n";
    }
    if (!empty($bcc)) {
        $headers .= "Bcc: $bcc\r\n";
    }
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    // Send the email
    if (mail($to, $subject, $body, $headers)) {
        $result = ["stdout" => "Email sent successfully.", "stderr" => "", "exit_code" => 0];
    } else {
        $result = ["stdout" => "", "stderr" => "Error (ash): Failed to send email.", "exit_code" => -1];
    }

    if ($this->ash->debug) echo ("debug: sendemail() result: " . print_r($result, true) . "\n");
    return $result;
};
