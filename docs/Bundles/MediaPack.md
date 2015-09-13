<h2>MediaPack, embed third-party media from a hundred different sites</h2>

### Example

```php
use s9e\TextFormatter\Bundles\MediaPack as TextFormatter;

$text = 'https://www.youtube.com/watch?v=QH2-TGUlwu4';
$xml  = TextFormatter::parse($text);
$html = TextFormatter::render($xml);

echo $html;
```
```html
<iframe width="560" height="315" allowfullscreen="" frameborder="0" scrolling="no" src="//www.youtube.com/embed/QH2-TGUlwu4"></iframe>
```
