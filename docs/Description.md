* [Goals](#goals)
* [Requirements](#requirements)
* [General features](#general-features)
* [Security](#security)
* [Plugins](#plugins)

## Goals

* Be as extensible as needed
* Be easy to integrate
* Be able to parse most of the markup used in popular forums, blogs, commenting systems
* Build on solid security features
* Stay performant

## Requirements

* Compatible with PHP 5.3, PHP 5.4+ recommended
	* You need to run [convert5.3.php](https://github.com/s9e/TextFormatter/blob/master/scripts/convert5.3.php) to convert the source from git to PHP 5.3
* [ext/dom](http://docs.php.net/manual/en/book.dom.php)
* [ext/filter](http://docs.php.net/manual/en/book.filter.php)
* [ext/pcre](http://docs.php.net/manual/en/book.pcre.php) with Unicode support
* [ext/xsl](http://docs.php.net/manual/en/book.xsl.php) recommended

## General features

* Modular
	* Comes with a dozen of plugins
	* Only use the plugins you want
	* Creating new plugins is easy and you don't have to worry about interactions

* Unit-tested
	* Tests are [automatically](https://github.com/s9e/TextFormatter/blob/master/scripts/pre-commit) run before every commit. A failing test blocks the commit
	* [Testdox output](https://github.com/s9e/TextFormatter/blob/master/docs/testdox.txt) is updated [with every commit](https://github.com/s9e/TextFormatter/commit/83d959aebe601eac207c499ef6922224b3958211)
	* Travis follows the [build status across the supported PHP versions: ![Build Status](https://travis-ci.org/s9e/TextFormatter.png?branch=master)](https://travis-ci.org/s9e/TextFormatter)
	* Aiming for [100% code coverage](http://s9e.github.io/TextFormatter/coverage/index.html) at all time

* Stable format
	* Formatted text is returned as XML, which can be read easily
	* The XML representation can be returned back to the original text with two exceptions:
		* CRLF (`\r\n`) is replaced with LF (`\n`)
		* Control characters (ASCII below 32) are removed, except for tabs and newlines

* Unstable API
	* The parsing/rendering API is simple enough that no major change is expected to take place
	* The configuring API is less stable
		* New APIs appear as new features are implemented
		* Tags/attributes API is relatively stable and hasn't changed significantly since 2012
		* Plugins API is relatively stable
		* Helpers living in `s9e\TextFormatter\Configurator\Helpers` are consolidated and abstracted as code evolves

* Tag control

	* BBCodes, HTML, Emoticons, every plugin uses *tags*, which is the underlying system that unifies all plugins. Most plugins have a 1:1 relation between their syntax and the underlying tags (e.g. the BBCode `[B]` will transparently use a tag called `B`)

	* Comprehensive [list of rules](https://github.com/s9e/TextFormatter/blob/master/docs/Rules.md) that control what tag is allowed where
		* Most of the rules can be automatically created, based on heuristics provided by the HTML5 specs
		```php
		<?php
		$configurator->addHTML5Rules();
		```

	* You can control the maximum number of times a given tag can be used, or how many times they can be nested into each other (limit set at the tag level)
	```php
	<?php
	$configurator->tags['QUOTE']->setNestingLimit(3);
	$configurator->tags['URL']->setTagLimit(1);
	```

	* Tag filters can alter a tag at parsing time and determine whether to invalidate it

* Attribute control

	* Tags can have attributes. The same way BBCodes and HTML elements have a 1:1 relation to tags, BBCode attributes and HTML attributes have a 1:1 to *tag attributes*
	* Attributes can be optional or required. A missing or invalid attribute invalidates its tag if it's required
	* Attributes can have a default value
	* Attributes can have any number of filters used for validation, sanitization, or any kind of transformation

* Templating

	* Uses XSLT
		* Syntax almost identical to XHTML, e.g.
		```xslt
		<a href="{@url}"><xsl:apply-templates/></a>
		```
		* Automatic escaping of HTML entities
		* Supports conditionals and user-set parameters

	* Offer multiple renderers
		* __XSLT__ uses [PHP's ext/xsl](http://docs.php.net/manual/en/book.xsl.php)
		* __PHP__ uses plain PHP (faster if you're using an opcode cache but does not support the whole range of XSLT. It *does* support everything listed on this page though)
		* __XSLCache__ uses PECL's [xslcache](http://pecl.php.net/package/xslcache) (fastest but [requires some patching for PHP 5.4+](https://bugs.php.net/bug.php?id=62856))

## Security

* Plugins do *not* modify the text, they only describe how to transform it
	* A bad plugin cannot accidentally introduce new XSS vectors

* XSLT automatically escapes special characters, so even unfiltered content cannot "break out" of an attribute
	* The PHP renderer follows the same logic

* Attributes (from BBCodes and other plugins) are filtered/sanitized
	* By type, e.g. number, numeric range, color, URL
	* By regular expression
	* You can create your own custom filters, or replace the default ones
	* URLs
		* URLs only accept http and https schemes. You can allow more URL schemes:
		```php
		<?php
		$configurator->urlConfig->allowScheme('ftp');
		```

		* You can set a blacklist of hosts that cause an URL to be invalid
		```php
		<?php
		// Bans example.org and all subdomains such as bad.example.org
		$configurator->urlConfig->disallowHost('example.org');
		// Bans example.com, example.org, etc...
		$configurator->urlConfig->disallowHost('example.*');
		```

		* You can force some redirectors to be resolved to reveal the actual URL, e.g.
		```php
		<?php
		$configurator->resolveRedirectsFrom('t.co');
		```

* Templates are systematically inspected

	* Improperly sanitized content is not allowed in a sensitive context:
		* Text that can contain quotes or parentheses is not allowed in JavaScript (e.g. in an onclick event), but numbers are
		* Unfiltered text is not allowed in a CSS context (e.g. in an style attribute) but colors are
		* Unfiltered text is not allowed *as* a URL, but content using the URL filter is

	* Other security checks [listed separately](https://github.com/s9e/TextFormatter/blob/master/docs/TemplateSecurity.md)

	* You can replace existing checks or add your own checks

	* Templates that are identified as unsafe cause an `s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException` to be thrown
		* `UnsafeTemplateException::highlightNode()` will return the source of the template with the part that has triggered the exception highlighted

## Plugins

* [Autoemail](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/Autoemail)

	* Turns email addresses in plain text into mailto: links

* [Autolink](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/Autolink)

	* Turns URLs in plain text into links

* [BBCodes](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/BBCodes)

	* Flexible BBCode parser

		* Can handle the syntax of most other BBCode parsers
		* Multiple attributes: `[flash width=100 height=50]`
		* Optional attributes: `[flash height=50]` *(will use the default width)*
		* Default attribute, e.g. `[size=1]`
		* Attribute values can be:
			* in single quotes: `[quote='Author']`
			* in double quotes: `[quote="Author"]`
			* unquoted, even if it contains spaces: `[quote=John Doe]`
			* quotes inside values can be escaped with a backslash `\`: `[quote='John "Johnny" D\'oh']`
		* BBCodes can be self-closed: `[hr/]`
		* BBCode names can be followed by a colon and a number to uniquely identify and pair them: `[i:123][/i][/i:123]`

	* BBCodes can be defined using [a syntax](https://github.com/s9e/TextFormatter/blob/master/docs/BBCodeMonkey.md) mostly compatible with [phpBB's](https://www.phpbb.com/customise/db/custom_bbcodes-26/), which makes it easy to create, import and distribute custom BBCodes:

		```php
		<?php
		$configurator->BBCodes->addCustom(
			'[COLOR={COLOR}]{TEXT}[/COLOR]',
			'<span style="color:{COLOR}">{TEXT}</span>'
		);
		```

	* Custom BBCodes can be organized in a repository. The [default repository](https://github.com/s9e/TextFormatter/blob/master/src/s9e/TextFormatter/Plugins/BBCodes/Configurator/repository.xml) comes with dozens of commonly requested BBCodes

* [Censor](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/Censor)

	* Censors words. Accepts jokers

* [Emoticons](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/Emoticons)

	* Lets you define emoticons and the corresponding HTML

* [Escaper](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/Escaper)

	* Provides an escaping mechanism using `\`

* [FancyPants](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/FancyPants)

	* Enhanced typography, similar to [SmartyPants](http://daringfireball.net/projects/smartypants/) and [RedCloth's Textile](http://redcloth.org/textile/writing-paragraph-text/#typographers-quotes)

* [Generic](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/Generic)

	* Performs regexp-based replacements
	* Mostly compatible with [MyBB's Custom MyCodes](http://community.mybb.com/thread-12008.html) but without the possibility of introducing XSS vectors

* [HTMLElements](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/HTMLElements)

	* Whitelist of HTML elements (each element has a whitelist of attributes)
* [HTMLEntities](https://github.com/s9e/TextFormatter/tree/master/src/s9e/TextFormatter/Plugins/HTMLEntities)

	* Turns HTML entities into their Unicode literal

* Upcoming plugins
	* MediaEmbed, offering features similar to Wordpress's media shortcodes or XenForo's media sites
	* Markdown, depending on demand