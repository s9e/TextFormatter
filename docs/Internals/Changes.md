See also [API changes](API_changes.md).


## 2.0.0

The [Autolink](../Plugins/Autolink/Synopsis.md) behaviour has changed:

 - A [low-priority](Tag_priorities.md) [verbatim](http://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addVerbatim) tag is used to protect the linked URL from partial replacements. This prevents markup from being interpreted inside of URLs while allowing whole replacements.


## 1.4.0

`$configurator->asConfig()` does not implicitly call `$configurator->plugins->finalize()` anymore. The latter remains available and can be called explicitly if necessary, before the configuration is generated.

`AVTHelper::parse()` now silently converts invalid XPath expressions to literals instead of throwing an exception.
