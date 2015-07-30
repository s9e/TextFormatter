## Introduction

s9e\TextFormatter can optionally generate a JavaScript parser that can be used in browsers to parse text and preview the result in a live environment.

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
```
