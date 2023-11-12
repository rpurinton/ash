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
            "```antlr" => "ANTLR:",
            "```apache" => "Apache Config:",
            "```awk" => "Awk Script:",
            "```bash" => "Bash Script:",
            "```bib" => "BibTeX:",
            "```bibtex" => "BibTeX:",
            "```bison" => "Bison:",
            "```bnf" => "BNF:",
            "```c" => "C:",
            "```clojure" => "Clojure:",
            "```cmake" => "CMake:",
            "```cpp" => "C++:",
            "```csharp" => "C#:",
            "```crystal" => "Crystal:",
            "```css" => "CSS:",
            "```dart" => "Dart:",
            "```d" => "D:",
            "```diff" => "Diff:",
            "```dockerfile" => "Dockerfile:",
            "```ebnf" => "EBNF:",
            "```elixir" => "Elixir:",
            "```erlang" => "Erlang:",
            "```fsharp" => "F#:",
            "```fish" => "Fish Script:",
            "```gitattributes" => "Git Attributes:",
            "```gitignore" => "Git Ignore:",
            "```gitmodules" => "Git Modules:",
            "```git" => "Git Config:",
            "```go" => "Go:",
            "```haskell" => "Haskell:",
            "```html" => "HTML:",
            "```ini" => "INI:",
            "```java" => "Java:",
            "```javascript" => "JavaScript:",
            "```json" => "JSON:",
            "```kotlin" => "Kotlin:",
            "```latex" => "LaTeX:",
            "```lisp" => "Lisp:",
            "```lua" => "Lua:",
            "```makefile" => "Makefile:",
            "```markdown" => "Markdown:",
            "```md" => "Markdown:",
            "```nginx" => "Nginx Config:",
            "```nim" => "Nim:",
            "```ocaml" => "OCaml:",
            "```pegjs" => "PEG.js:",
            "```perl" => "Perl:",
            "```php" => "PHP:",
            "```powershell" => "PowerShell:",
            "```python" => "Python:",
            "```r" => "R:",
            "```reason" => "Reason:",
            "```restructuredtext" => "reStructuredText:",
            "```rst" => "reStructuredText:",
            "```ruby" => "Ruby:",
            "```rust" => "Rust:",
            "```scala" => "Scala:",
            "```scheme" => "Scheme:",
            "```sed" => "Sed Script:",
            "```sh" => "Shell Script:",
            "```shell" => "Shell Script:",
            "```sql" => "SQL:",
            "```swift" => "Swift:",
            "```tex" => "TeX:",
            "```toml" => "TOML:",
            "```typescript" => "TypeScript:",
            "```vb" => "Visual Basic:",
            "```viml" => "Vim Script:",
            "```vim" => "Vim Script:",
            "```xml" => "XML:",
            "```yaml" => "YAML:",
            "```zsh" => "Zsh Script:"
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
        $text = stripcslashes($text);
        return $text;
    }
}
