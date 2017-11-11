## 0.12.0

Support for PHP 5.3 has been abandoned. Releases are now based on the `release/php5.4` branch, which requires PHP 5.4.7 or greater. The `release/php5.3` branch will remain active for some time but is unsupported.

The PHP renderer's source has been removed from the renderer instance.

The `HostedMinifier` and `RemoteCache` minifiers have been removed.

`s9e\TextFormatter\Configurator\Helpers\TemplateHelper::getMetaElementsRegexp()` has been removed.

`s9e\TextFormatter\Configurator\Helpers\TemplateInspector::getDOM()` has been removed.

The `metaElementsRegexp` property of `s9e\TextFormatter\Renderer` has been removed. Meta elements `e`, `i` and `s` are now always removed from the source XML before rendering.

The `quickRenderingTest` property of the PHP renderer is now protected.

`s9e\TextFormatter\Configurator\Helpers\XPathHelper::export()` has been moved to `s9e\TextFormatter\Utils\XPath::export()`.

`s9e\TextFormatter\Configurator\TemplateNormalization` has been replaced by `s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization`.

The `branchTableThreshold` property of `s9e\TextFormatter\Configurator\RendererGenerators\PHP\Serializer` has been removed.

The `generateConditionals()` and `generateBranchTable()` methods of `s9e\TextFormatter\Configurator\RendererGenerators\PHP\Quick` have been removed.

The template used by the Emoji plugin is now hardcoded and defaults to using EmojiOne's SVG assets. The following methods have been removed from its configurator:

 * `forceImageSize()`
 * `omitImageSize()`
 * `setImageSize()`
 * `useEmojiOne()`
 * `usePNG()`
 * `useuseSVG()`
 * `useTwemoji()`


## 0.11.0

The optional argument of `s9e\TextFormatter\Configurator\RulesGenerator::getRules()` has been removed.

The optional argument of `s9e\TextFormatter\Configurator::finalize()` has been removed.

The following methods have been removed:

 * `s9e\TextFormatter\Configurator::addHTML5Rules()`  
   Tag rules are systematically added during `finalize()`. See [Automatic rules generation](../Rules/Automatic_rules_generation.md).

 * `s9e\TextFormatter\Configurator\Collections\Ruleset::defaultChildRule()`  
   The default is now `deny`.

 * `s9e\TextFormatter\Configurator\Collections\Ruleset::defaultDescendantRule()`  
   The default is now `deny`.

 * `s9e\TextFormatter\Configurator\Helpers\TemplateInspector::isIframe()`

In addition, the meaning of the `allowDescendant` and `denyDescendant` rules have been changed to exclude the tag's child. See [Tag rules](../Rules/Tag_rules.md).


## 0.10.0

`s9e\TextFormatter\Plugins\Censor\Helper::reparse()` has been removed.

`s9e\TextFormatter\Parser\Tag::setSortPriority()` has been removed. See the [0.7.0 notes](#070) for more info.


## 0.9.0

`s9e\TextFormatter\Configurator\TemplateForensics` has been renamed to `s9e\TextFormatter\Configurator\TemplateInspector`.

`s9e\TextFormatter\Configurator\Items\Template::getForensics()` has been renamed to `s9e\TextFormatter\Configurator\Items\Template::getInspector()`.


## 0.8.0

The `s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteDefinitionProvider` interface has been removed.

`$configurator->MediaEmbed->defaultSites` is now an iterable collection that implements the `ArrayAccess` interface. See [its API](http://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Plugins/MediaEmbed/Configurator/Collections/SiteDefinitionCollection.html).


## 0.7.0

`s9e\TextFormatter\Parser\Tag::setSortPriority()` has been deprecated. It will emit a warning upon use and will be removed in a future version.

The following methods now accept an additional argument to set a tag's priority at the time of its creation:

 * [addBrTag](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addBrTag)
 * [addCopyTag](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addCopyTag)
 * [addEndTag](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addEndTag)
 * [addIgnoreTag](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addIgnoreTag)
 * [addParagraphBreak](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addParagraphBreak)
 * [addSelfClosingTag](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addSelfClosingTag)
 * [addStartTag](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addStartTag)
 * [addTagPair](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addTagPair)
 * [addVerbatim](https://s9e.github.io/TextFormatter/api/s9e/TextFormatter/Parser.html#method_addVerbatim)