## Change the template used to render embedded content

`add()` returns the tag that is associate with the newly-added media site. You can change its default template to change the way the embedded content is displayed. In the following example, we put YouTube videos inside of a special `<div>`.

```php
$configurator = new s9e\TextFormatter\Configurator;

$tag = $configurator->MediaEmbed->add('youtube');

$tag->defaultTemplate = '<div class="embed-youtube">'
                      . $tag->defaultTemplate
                      . '</div>';

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$text = '[youtube]http://www.youtube.com/watch?v=-cEzsCAzTak[/youtube]';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
<div class="embed-youtube"><iframe width="560" height="315" src="http://www.youtube.com/embed/-cEzsCAzTak" allowfullscreen="" frameborder="0" scrolling="no"></iframe></div>
```
