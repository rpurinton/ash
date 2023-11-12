<?php

namespace Rpurinton\Ash;

class Util
{
    private $encoder = null;

    public function __construct()
    {
        $this->encoder = new \TikToken\Encoder();
    }

    public function tokenCount(string $input): int
    {
        try {
            $count = count($this->encoder->encode($input));
        } catch (\Exception $e) {
            echo ("Error: " . print_r($e, true) . "\n");
            $count = 0;
        } catch (\Error $e) {
            echo ("Error: " . print_r($e, true) . "\n");
            $count = 0;
        } catch (\Throwable $e) {
            echo ("Error: " . print_r($e, true) . "\n");
            $count = 0;
        }
        return $count;
    }

    public function markdownToEscapeCodes($text, $color_support = true)
    {
        $replacements = [
            "```diff" => "Diff:",
            "```yaml" => "YAML:",
            "```json" => "JSON:",
            "```bash" => "Bash Script:",
            "```javascript" => "JavaScript:",
            "```html" => "HTML:",
            "```css" => "CSS:",
            "```typescript" => "TypeScript:",
            "```python" => "Python:",
            "```ruby" => "Ruby:",
            "```c" => "C:",
            "```cpp" => "C++:",
            "```csharp" => "C#:",
            "```go" => "Go:",
            "```java" => "Java:",
            "```kotlin" => "Kotlin:",
            "```rust" => "Rust:",
            "```scala" => "Scala:",
            "```swift" => "Swift:",
            "```dart" => "Dart:",
            "```elixir" => "Elixir:",
            "```erlang" => "Erlang:",
            "```haskell" => "Haskell:",
            "```lisp" => "Lisp:",
            "```lua" => "Lua:",
            "```ocaml" => "OCaml:",
            "```php" => "PHP:",
        ];

        if ($color_support) {
            // Handle nested markdown by replacing from the innermost to the outermost
            $text = preg_replace('/(?<!\\\\)\\`(.*?)\\`/', "\e[7m$1\e[0m", $text); // Inline code
            $text = preg_replace('/__(.*?)__/', "\e[4m$1\e[0m", $text); // Underline
            $text = preg_replace('/\*\*(.*?)\*\*/s', "\e[1m$1\e[0m", $text); // Bold
            $text = preg_replace('/\*(.*?)\*/s', "\e[3m$1\e[0m", $text); // Italic
            $text = preg_replace('/~~(.*?)~~/', "\e[9m$1\e[0m", $text); // Strikethrough
        } else {
            $text = preg_replace('/__(.*?)__/', "$1", $text);
            $text = preg_replace('/\*\*(.*?)\*\*/s', "$1", $text);
            $text = preg_replace('/\*(.*?)\*/s', "$1", $text);
            $text = preg_replace('/~~(.*?)~~/', "$1", $text);
        }

        // Handle URLs separately
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', "\e[34;4m$1\e[0m", $text);

        // Add any missing replacements here
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        $text = str_replace("```", "", $text);

        // Fix escaped characters that should be treated literally
        $text = str_replace("\\e", "\e", $text);
        $text = str_replace('\\\\', '\\', $text);

        return $text;
    }
}
