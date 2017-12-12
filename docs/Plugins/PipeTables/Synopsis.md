This plugin implements a type of ASCII-style tables inspired by GitHub-flavored Markdown, Pandoc's pipe tables and PHP Markdown Extra's simple tables.

See its [Syntax](Syntax.md).

### References

 * [GitHub-flavored Markdown](https://help.github.com/articles/organizing-information-with-tables/)
 * [Pandoc's pipe_tables extension](https://pandoc.org/MANUAL.html#extension-pipe_tables)
 * [PHP Markdown Extra](https://michelf.ca/projects/php-markdown/extra/#table)

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('PipeTables');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'a | b' . "\n"
      . '--|--' . "\n"
      . 'c | d';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<table><thead><tr><th>a</th><th>b</th></tr></thead>
<tbody><tr><td>c</td><td>d</td></tr></tbody></table>
```
