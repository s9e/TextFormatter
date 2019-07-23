<style>ul{font-size:16px}</style>

See also [API changes](API_changes.md).

## 2.1.0

The [Litedown](../Plugins/Litedown/Synopsis.md) behaviour has changed:

 - [Block spoilers](../Plugins/Litedown/Syntax.md#spoilers) and [inline spoilers](../Plugins/Litedown/Syntax.md#inline-spoilers) have been added.
 - A forced line break forces the next newline character (U+000A) to be output verbatim. This ensures that only one line break occurs even if automatic line breaks are enabled.


## 2.0.0

The [Autolink](../Plugins/Autolink/Synopsis.md) behaviour has changed:

 - A [low-priority](Tag_priorities.md) [verbatim](http://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addVerbatim) tag is used to protect the linked URL from partial replacements. This prevents markup from being interpreted inside of URLs while allowing whole replacements.

The [Emoji](../Plugins/Emoji/Synopsis.md) configurator has changed:

 - The attribute name is now hardcoded.
 - The default template uses Twemoji's assets.


## 1.4.0

`$configurator->asConfig()` does not implicitly call `$configurator->plugins->finalize()` anymore. The latter remains available and can be called explicitly if necessary, before the configuration is generated.

`AVTHelper::parse()` now silently converts invalid XPath expressions to literals instead of throwing an exception.
