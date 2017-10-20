## Introduction

s9e\TextFormatter can optionally generate a JavaScript parser that can be used in browsers to parse text and preview the result in a live environment. The JavaScript parser requires Internet Explorer 8 or later, or any contemporary browser that supports [XSLTProcessor](https://developer.mozilla.org/en-US/docs/Web/API/XSLTProcessor#Browser_compatibility).

```php
$configurator = new s9e\TextFormatter\Configurator;

// Enable the JavaScript parser
$configurator->enableJavaScript();

// Now finalize() will return an entry for "js"
extract($configurator->finalize());
```

With JavaScript enabled, `finalize()` will return an element named `js` that contains the JavaScript source for the `s9e.TextFormatter` JavaScript object.

### API

```js
// Parse $text and return the XML as a string
s9e.TextFormatter.parse($text);

// Parse $text and preview it in DOMElement $target
s9e.TextFormatter.preview($text, $target);

// Toggle a plugin by name
s9e.TextFormatter.disablePlugin($pluginName);
s9e.TextFormatter.enablePlugin($pluginName);

// Toggle a tag by name
s9e.TextFormatter.disableTag($tagName);
s9e.TextFormatter.enableTag($tagName);

// Runtime configuration
s9e.TextFormatter.setNestingLimit($tagName, $limit);
s9e.TextFormatter.setTagLimit($tagName, $limit);
```

### Minify the JavaScript parser with the Google Closure Compiler service

The JavaScript parser can be automatically be minified using the [Google Closure Compiler service](https://developers.google.com/closure/compiler/docs/gettingstarted_api) via HTTP. The minification level and other configuration are automatically set.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();
$configurator->javascript->setMinifier('ClosureCompilerService');
```

[Other minifiers are available.](Minifiers.md)

### Speed up minification with a cache

The result of minification can be cached locally and reused. It's only useful if the JavaScript parser is regenerated more often than the configuration changes, since any modification to the configuration produces a different source.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();

$configurator->javascript
	->setMinifier('ClosureCompilerService')
	->cacheDir = '/path/to/cache';
```

### Improve minification

It is possible to improve the minification ratio by disabling features that are not used. For instance, if you only use the `parse` and `preview` methods you can reduce the API to only those two methods.

```php
$configurator = new s9e\TextFormatter\Configurator;
$configurator->enableJavaScript();

$configurator->javascript->exportMethods = [
	'parse',
	'preview'
];
```
