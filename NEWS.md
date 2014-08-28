###### August 2014

- Reworked the algorithm and rules defining automatic and manual line breaks. Enables forced line breaks in Litedown.

###### July 2014

- Implemented a fast PHP renderer dubbed the Quick renderer. Can be enabled with `$configurator->rendering->setEngine('PHP')->enableQuickRenderer = true`
- Cleaned up some internal helpers: TemplateParser, XPathParser, PHP renderer generator.

###### February 2014

- Added [Fatdown](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/10_Bundles/Fatdown.md), a Markdown-like bundle that doesn't suck.
- The default #url filter now accepts relative URLs.
