## How to toggle whether emoticons should be displayed

The property `notIfCondition` can be set to an XPath expression that, when it evaluates to true, forces emoticons to be rendered as their original text.

In the following example, we configure the Emoticons plugin to display emoticons as text if the parameter `$no_emoticons` is true.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Emoticons->add(':)', '<img src="happy.png" alt=":)" title="Happy">');

// Set the XPath condition
$configurator->Emoticons->notIfCondition = '$no_emoticons';

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Toggling emoticons :)';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html, "\n";

// Now set $no_emoticons and try rendering the same text again
$renderer->setParameter('no_emoticons', true);
$html = $renderer->render($xml);

echo $html;
```
```html
Toggling emoticons <img src="happy.png" alt=":)" title="Happy">
Toggling emoticons :)
```
