This plugin provides enhanced typography, aka "fancy Unicode symbols." It is inspired by [SmartyPants](http://daringfireball.net/projects/smartypants/) and [RedCloth's Textile](http://redcloth.org/textile/writing-paragraph-text/#typographers-quotes).

## Example

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('FancyPants');

// Get an instance of the parser and the renderer
extract($configurator->finalize());

$text = 'Fancy "quotes", symbols (c)(tm), dashes -- and elipsis...';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
Fancy “quotes”, symbols ©™, dashes – and elipsis…
```

## How to disable a pass

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->FancyPants->disablePass('Quotes');

extract($configurator->finalize());

$text = '<<guillemets>> are replaced but not "quotes"';
$xml  = $parser->parse($text);
$html = $renderer->render($xml);

echo $html;
```
```html
«guillemets» are replaced but not "quotes"
```

## Passes

<table>
	<thead>
		<tr>
			<th>Name</th>
			<th>Original</th>
			<th>Fancy</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>Guillemets</td>
			<td>&lt;&lt;Guillemets&gt;&gt;</td>
			<td>«Guillemets»</td>
		</tr>
		<tr>
			<td>MathSymbols</td>
			<td>a != b. 2" x 4" planks.</td>
			<td>a ≠ b. 2″ × 4″ planks.</td>
		</tr>
		<tr>
			<td>Punctuation</td>
			<td>En dash -- Em dash --- Ellipsis ...</td>
			<td>En dash – Em dash — Ellipsis …</td>
		</tr>
		<tr>
			<td>Quotes</td>
			<td>"Double" or 'single'</td>
			<td>“Double” or ‘single’</td>
		</tr>
		<tr>
			<td>Symbols</td>
			<td>(c) (r) (tm)</td>
			<td>© ® ™</td>
		</tr>
	</tbody>
</table>