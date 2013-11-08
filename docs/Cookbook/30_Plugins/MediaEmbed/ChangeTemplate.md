## Change the template used to render embedded content

`add()` returns the tag that is associate with the newly-added media site. You can change its default template to change the way the embedded content is displayed. In the following example, we put YouTube videos inside of a special `<div>`.

```php
$configurator = new s9e\TextFormatter\Configurator;

$tag = $configurator->MediaEmbed->add('youtube');

$tag->defaultTemplate = '<div class="embed-youtube">'
                      . $tag->defaultTemplate
                      . '</div>';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[youtube]http://www.youtube.com/watch?v=-cEzsCAzTak[/youtube]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<div class="embed-youtube"><iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/-cEzsCAzTak"></iframe></div>
```
