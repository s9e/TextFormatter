<h2>Localize strings in a BBCode template</h2>

One way to localize a BBCode template is to use a [template parameter](../../Templating/Template_parameters.md). When creating a custom BBCode, any token that is not associated with a filter is presumed to be a template parameter. In the following example, we create a rudimentary `[spoiler]` BBCode that uses a normal token `{TEXT}` for its text and the tokens `{L_SPOILER}`, `{L_TOGGLE}` for the localized strings. Any uppercase names would work here, as long as they don't correspond to the name of a [built-in filter](../../Filters/Built-in_filters.md). Before rendering, we set the values of the localized strings.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addCustom(
	'[spoiler]{TEXT}[/spoiler]',
	<<<EOT
	<div class="spoiler">
		<div class="spoiler-header">
			<span class="spoiler-title">{L_SPOILER}</span>
			<input type="button" value="{L_TOGGLE}" onclick="var s=this.parentNode.nextSibling.style;s.display=(s.display)?'':'none';"/>
		</div>
		<div class="spoiler-content" style="display:none">{TEXT}</div>
	</div>
EOT
);

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = '[spoiler]Gandalf kills Voldermort[/spoiler]';
$xml  = $parser->parse($text);

// Set up the values before rendering
$renderer->setParameter('L_SPOILER', 'Spoiler: ');
$renderer->setParameter('L_TOGGLE',  'Show/Hide');

// Render the text
$html = $renderer->render($xml);

echo $html;
```
```html
<div class="spoiler"><div class="spoiler-header"><span class="spoiler-title">Spoiler: </span><input type="button" value="Show/Hide" onclick="var s=this.parentNode.nextSibling.style;s.display=(s.display)?'':'none';"></div><div class="spoiler-content" style="display:none">Gandalf kills Voldermort</div></div>
```
