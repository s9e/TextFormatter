var hasSingleQuote = (text.indexOf("'") >= 0),
	hasDoubleQuote = (text.indexOf('"') >= 0);

// Do apostrophes ’ after a letter or at the beginning of a word or a couple of digits
if (hasSingleQuote)
{
	parseSingleQuotes(text);
}

// Do symbols found after a digit:
//  - apostrophe ’ if it's followed by an "s" as in 80's
//  - prime ′ and double prime ″
//  - multiply sign × if it's followed by an optional space and another digit
if (hasSingleQuote || hasDoubleQuote || text.indexOf('x') >= 0)
{
	parseSymbolsAfterDigits(text);
}

// Do quote pairs ‘’ and “” -- must be done separately to handle nesting
if (hasSingleQuote)
{
	parseSingleQuotePairs();
}
if (hasDoubleQuote)
{
	parseDoubleQuotePairs();
}

// Do en dash –, em dash — and ellipsis …
if (text.indexOf('...') >= 0 || text.indexOf('--')  >= 0)
{
	parseDashesAndEllipses();
}

// Do symbols ©, ® and ™
if (text.indexOf('(') >= 0)
{
	parseSymbolsInParentheses();
}

/**
* Parse dashes and ellipses
*/
function parseDashesAndEllipses()
{
	var chrs = {
		'--'  : "\u2013",
		'---' : "\u2014",
		'...' : "\u2026"
	};

	// '/---?|\\.\\.\\./S'
	var m, regexp = /---?|\.\.\./g;
	while (m = regexp.exec(text))
	{
		var pos = m['index'],
			len = m[0].length,
			chr = chrs[m[0]];

		addSelfClosingTag(config.tagName, pos, len).setAttribute(config.attrName, chr);
	}
}

/**
* Parse pairs of double quotes
*/
function parseDoubleQuotePairs()
{
	// '/(?<![0-9\\pL])"[^"\\n]+"(?![0-9\\pL])/uS'
	parseQuotePairs('"', /(?:^|\W)".+?"(?!\w)/g, "\u201c", "\u201d");
}

/**
* Parse pairs of quotes
*
* @param {!string} q          ASCII quote character 
* @param {!string} regexp     Regexp used to identify quote pairs
* @param {!string} leftQuote  Fancy replacement for left quote
* @param {!string} rightQuote Fancy replacement for right quote
*/
function parseQuotePairs(q, regexp, leftQuote, rightQuote)
{
	var m;
	while (m = regexp.exec(text))
	{
		var left  = addSelfClosingTag(config.tagName, m['index'] + m[0].indexOf(q), 1),
			right = addSelfClosingTag(config.tagName, m['index'] + m[0].length - 1, 1);

		left.setAttribute(config.attrName, leftQuote);
		right.setAttribute(config.attrName, rightQuote);

		// Cascade left tag's invalidation to the right so that if we skip the left quote,
		// the right quote is left untouched
		left.cascadeInvalidationTo(right);
	}
}

/**
* Parse pairs of single quotes
*/
function parseSingleQuotePairs()
{
	// "/(?<![0-9\\pL])'[^'\\n]+'(?![0-9\\pL])/uS"
	parseQuotePairs("'", /(?:^|\W)'.+?'(?!\w)/g, "\u2018", "\u2019");
}

/**
* Parse single quotes in general
*/
function parseSingleQuotes(text)
{
	// "/(?<=\\pL)'|(?<!\\S)'(?=\\pL|[0-9]{2})/uS"
	var m, regexp = /[a-z]'|(?:^|\s)'(?=[a-z]|[0-9]{2})/gi;

	while (m = regexp.exec(text))
	{
		var tag = addSelfClosingTag(config.tagName, m['index'] + m[0].indexOf("'"), 1);
		tag.setAttribute(config.attrName, "\u2019");

		// Give this tag a worse priority than default so that quote pairs take precedence
		tag.setSortPriority(10);
	}
}

/**
* Parse symbols found after digits
*/
function parseSymbolsAfterDigits(text)
{
	// '/[0-9](?:\'s|["\']? ?x(?= ?[0-9])|["\'])/S'
	var m, regexp = /[0-9](?:'s|["']? ?x(?= ?[0-9])|["'])/g;

	while (m = regexp.exec(text))
	{
		// Test for a multiply sign at the end
		var pos = m['index'] + m[0].length - 1;
		if (m[0].charAt(pos) === 'x')
		{
			addSelfClosingTag(config.tagName, pos, 1).setAttribute(config.attrName, "\u00d7");
		}

		// Test for a apostrophe/prime right after the digit
		var c = m[0].charAt(1);
		if (c === "'" || c === '"')
		{
			pos = 1 + m['index'];

			var chr;
			if (m[0].substr(1, 2) === "'s")
			{
				// 80's -- use an apostrophe
				chr = "\u2019";
			}
			else
			{
				// 12' or 12" -- use a prime
				chr = (c === "'") ? "\u2032" : "\u2033";
			}

			addSelfClosingTag(config.tagName, pos, 1).setAttribute(config.attrName, chr);
		}
	}
}

/**
* Parse symbols found in parentheses such as (c)
*/
function parseSymbolsInParentheses()
{
	var chrs = {
		'(c)'  : "\u00A9",
		'(r)'  : "\u00AE",
		'(tm)' : "\u2122"
	};

	// '/\\((?:c|r|tm)\\)/i'
	var m, regexp = /\((?:c|r|tm)\)/gi;
	while (m = regexp.exec(text))
	{
		var pos = m['index'],
			len = m[0].length,
			chr = chrs[m[0].toLowerCase()];

		addSelfClosingTag(config.tagName, pos, len).setAttribute(config.attrName, chr);
	}
}