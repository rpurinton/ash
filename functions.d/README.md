ash uses a system of ChatGPT function to perform actions.
all of the function definitions are stored in /var/www/ash/src/functions.d.
for each function there is 1 .json file and 1 .php file.

a basic example of a .json would be:
```json
{
    "name": "read_file",
    "description": "Read the contents of a file and return the contents as a string.",
    "parameters": {
        "type": "object",
        "properties": {
            "path": {
                "type": "string",
                "description": "The path to the file to be read"
            }
        },
        "required": [
            "path"
        ]
    }
}
```

a basic example of a .php file for this function would be:
```php
<?php
$this->toolHandlers['read_file'] = function ($args) {
    if ($this->ash->debug) echo ("debug: read_file(" . print_r($args, true) . ")\n");
    $path = $args['path'] ?? "";
    echo ("$path\n"); // display just the main argument
    if (!file_exists($path)) {
        if ($this->ash->debug) echo ("debug: read_file() error: file not found: \"$path\"\n");
        return ["stdout" => "", "stderr" => "Error (ash): file not found: $path", "exit_code" => -1];
    }
    $result = ["stdout" => file_get_contents($path), "stderr" => "", "exit_code" => 0];
    if ($this->ash->debug) echo ("debug: read_file() result: " . print_r($result, true) . "\n");
    return $result;
};
```

as long as both files exist and are in the proper format the function will be available to ash in the next message.