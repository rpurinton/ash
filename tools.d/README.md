ash uses a system of ChatGPT function to perform actions.
for each function there is 1 .json file and 1 .php file.

a basic example of a .json would be:
```json
{
    "name": "read_file",
    "description": "Read the contents of a file and return the contents as a string.  Supports any plain text format, code, etc.  PDF and EPUB files will be converted to text.  Image formats will be OCR'd and converted to text. Not for use with binary files.  Do not read files more than 64KB in size or it wont fit in the context window.",
    "strict": true,
    "parameters": {
        "type": "object",
        "required": [
            "path"
        ],
        "properties": {
            "path": {
                "type": "string",
                "description": "The path to the file to be read"
            }
        },
        "additionalProperties": false
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
    $contents = file_get_contents($path);
    if ($contents === false) {
        if ($this->ash->debug) echo ("debug: read_file() error: file could not be read: \"$path\"\n");
        return ["stdout" => "", "stderr" => "Error (ash): file could not be read: $path", "exit_code" => -1];
    }
    if ($contents == "") {
        if ($this->ash->debug) echo ("debug: read_file() error: file is empty: \"$path\"\n");
        return ["stdout" => "", "stderr" => "Error (ash): file is empty: $path", "exit_code" => -1];
    }
    $result = ["stdout" => $contents, "stderr" => "", "exit_code" => 0];
    if ($this->ash->debug) echo ("debug: read_file() result: " . print_r($result, true) . "\n");
    return $result;
};

```

as long as both files exist and are in the proper format the function will be available to ash in the next message.