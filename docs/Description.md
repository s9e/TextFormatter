* [Goals](#goals)
* [Requirements](#requirements)
* [General features](#general-features)
* [Security](#security)
* [Performance](#performance)
* [Plugins](#plugins)

## Goals

* Be as extensible as needed
* Be easy to integrate
* Be able to parse most of the markup used in popular forums, blogs, commenting systems
* Build on solid security features
* Stay performant

## Requirements

* PHP 5.3.3 or later (see [Installation](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/00_Getting_started/00_Installation.md))
* [ext/dom](http://docs.php.net/manual/en/book.dom.php)
* [ext/filter](http://docs.php.net/manual/en/book.filter.php) unless you implement your own validation
* Optional
   * [ext/intl](http://docs.php.net/manual/en/book.intl.php) for allowing international domain names
   * [ext/json](http://docs.php.net/manual/en/book.json.php) for generating the JavaScript parser
   * [ext/mbstring](http://docs.php.net/manual/en/book.mbstring.php) enables some optimizations in the PHP renderer
   * [ext/tokenizer](http://docs.php.net/manual/en/book.tokenizer.php) enables most optimizations in the PHP renderer
   * [ext/xsl](http://docs.php.net/manual/en/book.xsl.php) enables the XSL renderer
   * [ext/zlib](http://docs.php.net/manual/en/book.zlib.php) enables gzip compression when scraping content

## General features

* Modular
	* Comes with a dozen of plugins
	* Only use the plugins you want
	* Creating new plugins is easy and you don't have to worry about interactions

* Unit-tested
	* Tests are [automatically](https://github.com/s9e/TextFormatter/blob/master/scripts/pre-commit) run before every commit. A failing test blocks the commit
	* [Testdox output](https://github.com/s9e/TextFormatter/blob/master/docs/testdox.txt) is updated [with every commit](https://github.com/s9e/TextFormatter/commit/83d959aebe601eac207c499ef6922224b3958211)
	* Travis follows the [build status across the supported PHP versions: ![Build Status](https://api.travis-ci.org/s9e/TextFormatter.svg?branch=master)](https://travis-ci.org/s9e/TextFormatter)
	* Aiming for [100% code coverage](http://s9e.github.io/TextFormatter/coverage/) at all time [![Coverage Status](https://coveralls.io/repos/s9e/TextFormatter/badge.png)](https://coveralls.io/r/s9e/TextFormatter)

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
		* Most of the rules are automatically created, based on heuristics provided by the HTML5 specs

	* You can control the maximum number of times a given tag can be used, or how many times they can be nested into each other (limit set at the tag level)

		```php
		$configurator->tags['QUOTE']->nestingLimit = 3;
		$configurator->tags['URL']->tagLimit = 1;
		```

	* Tag filters can alter a tag at parsing time and determine whether to invalidate it

	* No misnesting, or dangling tags. `[b][i][/b][/i]` will never produce malformed HTML

* Attribute control

	* Tags can have attributes. The same way BBCodes and HTML elements have a 1:1 relation to tags, BBCode attributes and HTML attributes have a 1:1 relation to *tag attributes*
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
		* Supports conditionals and user-set parameters (template variables)
		* Cannot produce malformed HTML
			* ...unless you purposely disable some of the template checks and manually disable the escaping

	* Is extensible
		* ...as long as [you can convert it back to XSLT](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/40_Templating/Template_normalization/03_Extends.md)

	* Offers multiple renderers
		* the __XSLT__ renderer uses [PHP's ext/xsl](http://docs.php.net/manual/en/book.xsl.php)
		* the __PHP__ renderer uses plain PHP (faster if you're using an opcode cache but does not support the whole range of XSLT. It *does* support everything listed on this page though)

## Security

* Plugins do *not* modify the text, they only describe how to transform it
	* A bad plugin cannot accidentally introduce new XSS vectors
	* ...but a bad renderer could, that's why they're extensively tested

* XSLT automatically escapes special characters, so even unfiltered content cannot "break out" of an attribute
	* The PHP renderer follows the same logic and run the same tests (plus a few more)

* Attributes (from BBCodes and other plugins) are filtered/sanitized
	* By type, e.g. number, numeric range, color, URL
	* By regular expression
	* You can create your own custom filters, or replace the default ones
	* URLs
		* URLs only accept http and https schemes. You can allow more URL schemes:

			```php
			$configurator->urlConfig->allowScheme('ftp');
			```

		* You can set a blacklist of hosts that cause an URL to be invalid

			```php
			// Bans example.org and all subdomains such as bad.example.org
			$configurator->urlConfig->disallowHost('example.org');
			// Bans example.com, example.org, etc...
			$configurator->urlConfig->disallowHost('example.*');
			```

		* ...or you can set a whitelist of hosts

			```php
			// Bans everything except example.org, example.com and all their subdomains
			$configurator->urlConfig->restrictHost('example.org');
			$configurator->urlConfig->restrictHost('example.com');
			```

* Templates are systematically inspected

	* Content that is not properly sanitized is not allowed in a sensitive context:
		* Text that can contain quotes or parentheses is not allowed in JavaScript (e.g. in an onclick event), but numbers are
		* Unfiltered text is not allowed in a CSS context (e.g. in an style attribute) but colors are
		* Unfiltered text is not allowed *as* a URL, but attributes using the URL filter are

	* Other security checks [listed separately](https://github.com/s9e/TextFormatter/blob/master/docs/TemplateSecurity.md)

	* You can replace existing checks or add your own checks

	* Templates that are identified as unsafe cause an `s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException` to be thrown
		* `UnsafeTemplateException::highlightNode()` will return the source of the template with the part that has triggered the exception highlighted

## Performance

* Designed to clearly separate [configuration](https://github.com/s9e/TextFormatter/tree/master/src/Configurator), [parsing](https://github.com/s9e/TextFormatter/tree/master/src/Parser) and [rendering](https://github.com/s9e/TextFormatter/tree/master/src/Renderers). Configuration happens rarely (if ever), parsing is run on every new text and rendering is run everytime a text is displayed
* Configuration can run thousands of lines of code across dozens of files but parsing only runs a few hundreds of lines across a few files and rendering only runs a few dozens of lines across a couple of files. Complexity is pushed towards configuration and parsing to keep rendering simple and fast
* The goal is to run configuration in less than a second, parsing in less than 100 ms and rendering in less than 10 ms
	* In actuality it's closer to 100 ms/10 ms/1 ms depending on content and hardware
* The source code in the release branches is automatically optimized for speed. The codebase is modified to produce slightly better opcodes. The code used in [development looks nice](https://github.com/s9e/TextFormatter/blob/master/src/Parser.php), the code run in production and testing [doesn't look as nice](https://github.com/s9e/TextFormatter/blob/release/php5.6/src/Parser.php) but runs slightly faster

## Plugins

* [Autoemail](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Autoemail)

	* Turns email addresses in plain text into mailto: links

* [Autoimage](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Autoimage)

	* Turns image URLs in plain text into images

* [Autolink](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Autolink)

	* Turns URLs in plain text into links

* [Autovideo](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Autovideo)

	* Turns video URLs in plain text into playable videos

* [BBCodes](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/BBCodes)

	* Flexible BBCode parser

		* Can handle the syntax of most other BBCode parsers
		* Multiple attributes: `[flash width=100 height=50]`
		* Optional attributes: `[flash height=50]` *(will use the default width)*
		* Default attribute, e.g. `[size=1]`
		* Composite attributes: `[flash=100,50]` *(values for height and width extracted by an attribute preprocessor)*
		* Attribute values can be:
			* in single quotes: `[quote='Author']`
			* in double quotes: `[quote="Author"]`
			* unquoted, even if it contains spaces: `[quote=John Doe]`
			* quotes inside values can be escaped with a backslash `\`: `[quote='John "Johnny" D\'oh']`
		* BBCodes can be self-closed: `[hr/]`
		* BBCode names can be followed by a colon and a number to uniquely identify and pair them: `[i:123][/i][/i:123]`

	* BBCodes can be defined using [a syntax](https://github.com/s9e/TextFormatter/blob/master/docs/BBCodeMonkey.md) mostly compatible with [phpBB's](https://www.phpbb.com/customise/db/custom_bbcodes-26/), which makes it easy to create, import and distribute custom BBCodes:

		```php
		$configurator->BBCodes->addCustom(
			'[COLOR={COLOR}]{TEXT}[/COLOR]',
			'<span style="color:{COLOR}">{TEXT}</span>'
		);
		```

	* Custom BBCodes can be organized in a repository. The [default repository](https://github.com/s9e/TextFormatter/blob/master/src/Plugins/BBCodes/Configurator/repository.xml) comes with dozens of commonly requested BBCodes

* [Censor](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Censor)

	* Censors words. Accepts jokers

* [Emoji](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Emoji)

	* Automatically renders emoji as images. Can use the emoji sets from [Twemoji](http://twitter.github.io/twemoji/) or [EmojiOne](http://www.emojione.com/)

* [Emoticons](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Emoticons)

	* Lets you define emoticons and the corresponding HTML

* [Escaper](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Escaper)

	* Provides an escaping mechanism using `\`

* [FancyPants](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/FancyPants)

	* Enhanced typography, similar to [SmartyPants](http://daringfireball.net/projects/smartypants/) and [RedCloth's Textile](http://redcloth.org/textile/writing-paragraph-text/#typographers-quotes)

* [HTMLComments](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/HTMLComments)

	* Supports the use of HTML comments

* [HTMLElements](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/HTMLElements)

	* Whitelist of HTML elements (each element has a whitelist of attributes)

* [HTMLEntities](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/HTMLEntities)

	* Turns HTML entities into their Unicode literal

* [Keywords](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Keywords)

	* Automatically replace a given list of keywords, e.g. Magic: the Gathering cards (autocard)
	* Very efficient

* [Litedown](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Litedown)

	* Markdown-like syntax
	* Only the good parts: no raw HTML, no obscure markup

* [MediaEmbed](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/MediaEmbed)

	* Creates a `[media]` BBCode for embedded content
		* Optionally creates a site-specific BBCode (e.g. `[youtube]`) for every available site
	* Optionally replaces URLs in plain text with embedded content
	* Has built-in support for Dailymotion, Facebook, LiveLeak, Twitch, YouTube [and dozens more](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/MediaEmbed/Configurator/sites/)
	* Flexible syntax
	* Comparable to [XenForo's media sites](http://xenforo.com/help/bb-code-media-sites/)

* [Preg](https://github.com/s9e/TextFormatter/tree/master/src/Plugins/Preg)

	* Performs regexp-based replacements although it does *not* use `preg_replace()`
	* Mostly compatible with [MyBB's Custom MyCodes](http://community.mybb.com/thread-12008.html)
	* Subject to the same filtering and validation as other plugins, meaning it offers the same protection against XSS
