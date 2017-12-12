<h2>Whitelist specific words to escape the censor</h2>

Occasionally, broad patterns may censor good words while trying to filter bad ones. This is known as [the Scunthorpe problem](https://en.wikipedia.org/wiki/Scunthorpe_problem). You can whitelist specific words to make them immune to the Censor plugin.

In the following example, we configure the Censor plugin to censor any word that contains "thor" except for "Scunthorpe" which is specifically allowed.

```php
$configurator = new s9e\TextFormatter\Configurator;

// Censor anything with "thor" in it
$configurator->Censor->add('*thor*');

// Allow "Scunthorpe"
$configurator->Censor->allow('scunthorpe');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Have you seen the new Thor movie? I thoroughly enjoyed it while I was in Scunthorpe.';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Have you seen the new **** movie? I **** enjoyed it while I was in Scunthorpe.
```
