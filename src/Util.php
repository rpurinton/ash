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
            "```" => ""
        ];
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);

        if ($color_support) {
            $text = preg_replace('/\*\*(.*?)\*\*/', "\e[1m$1\e[0m", $text); // Bold
            $text = preg_replace('/\*(.*?)\*/', "\e[3m$1\e[0m", $text); // Italic
            $text = preg_replace('/_(.*?)_/', "\e[4m$1\e[0m", $text); // Underline
            $text = preg_replace('/`(.*?)`/', "\e[48;5;226m$1\e[0m", $text); // Highlight
        } else {
            $text = preg_replace('/\*\*(.*?)\*\*/', "$1", $text); // Bold
            $text = preg_replace('/\*(.*?)\*/', "$1", $text); // Italic
            $text = preg_replace('/_(.*?)_/', "$1", $text); // Underline
            $text = preg_replace('/`(.*?)`/', "$1", $text); // Highlight
        }

        // urls
        $text = preg_replace('/\[(.*?)\]\((.*?)\)/', "\e[34;4m$1\e[0m", $text);
        $text = str_replace("\\e", "\e", $text);

        return $text;
    }
}
