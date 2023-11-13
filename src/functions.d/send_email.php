<?php
$this->functionHandlers['send_email'] = function ($args) {
    if ($this->ash->debug) echo ("debug: sendemail(" . print_r($args, true) . ")\n");

    // Extract arguments
    $to = $args['to'] ?? "";
    $subject = $args['subject'] ?? "";
    $body = $args['body'] ?? "";

    if (!isset($args['to']) || empty($args['to']) || !isset($args['subject']) || empty($args['subject']) || !isset($args['body']) || empty($args['body'])) {
        $error = "Error (ash): Missing required fields.";
        if ($this->ash->debug) echo ("debug: send_email() error: $error\n");
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }

    echo ("$to\n"); // display just the main argument

    // Optional arguments
    $cc = $args['cc'] ?? "";
    $bcc = $args['bcc'] ?? "";

    // Basic validation
    if (empty($to) || empty($subject) || empty($body)) {
        $error = "Error (ash): Missing required fields.";
        if ($this->ash->debug) echo ("debug: send_email() error: $error\n");
        return ["stdout" => "", "stderr" => $error, "exit_code" => -1];
    }

    // Build the email headers
    $headers = "From: " . $this->ash->config->config["fromAddress"] . "\r\n";
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
