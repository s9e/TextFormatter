### Define tags

First you need to *define* tags during configuration. In this example, we create a tag named `B` that wraps its content in bold with the following template: `<b><xsl:apply-templates/></b>`.

```php
$configurator = new s9e\TextFormatter\Configurator;

$tag = $configurator->tags->add('B');
$tag->template = '<b><xsl:apply-templates/></b>';
```

### Use tags during parsing

This section is unfinished.
In the meantime, see [addStartTag](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addStartTag)().
