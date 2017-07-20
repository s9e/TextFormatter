In an effort to control the resources used during Parsing, s9e\TextFormatter enforces various limits. None of them can be disabled, but all of them can be set to an arbitrarily high number. Those limits are reset before each parsing.

The default values are meant to be set high enough not to be reached by legitimate input such as user comments or a multi-page article, while limiting the effect of malicious input crafted to maximize resource usage.

<dl>
	<dt>regexpLimit</dt>
	<dd><i>(default: 10000)</i></dd>
	<dd>Works at the plugin level, e.g. <code>$configurator->Emoticons->setRegexpLimit(100);</code></dd>
	<dd>Most plugins (BBCodes, Emoticons, etc...) use a regexp to identify the parts of the text they apply to. This setting limits the number of matches to process, with the supernumary matches being ignored.</dd>

	<dt>tagLimit</dt>
	<dd><i>(default: 1000)</i></dd>
	<dd>Works at the tag level, e.g. <code>$configurator->tags['URL']->tagLimit = 5;</code></dd>
	<dd>This setting limits the number of times a given tag can be used. When the limit is reached, subsequent uses of this tag are ignored.</dd>

	<dt>nestingLimit</dt>
	<dd><i>(default: 10)</i></dd>
	<dd>Works at the tag level, e.g. <code>$configurator->tags['QUOTE']->nestingLimit = 2;</code></dd>
	<dd>This setting limits how deeply a given tag can be nested into itself. This number includes the outermost tag. For instance, the example above means that a QUOTE tag (first level) can have any number of QUOTE descendants (second level) but they can't themselves have any other QUOTE descendants (which would be the third level.)</dd>

	<dt>maxFixingCost</dt>
	<dd><i>(default: 10000)</i></dd>
	<dd>Works at the parser level, e.g. <code>$parser->maxFixingCost = 0;</code></dd>
	<dd>This arbitrary value controls how hard the parser will attempt to fix quirky markup. For instance, in the sequence <code>[b][i][/b]</code>, there's still an open <code>[i]</code> BBCode when its parent <code>[b]</code> gets closed. The parser fixes this situation by automatically closing <code>[i]</code>. This action would be considered to cost 1. Other actions, such as fixing misnested tags such as in <code>[b][i][/b][/i]</code> are estimated to cost another 1 or 2. Once this limit is exceeded, the parser stops trying to fix bad markup and an error is logged.</dd>
</dl>