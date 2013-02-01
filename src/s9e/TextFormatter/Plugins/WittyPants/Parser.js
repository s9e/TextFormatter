var attrName = config['attrName'],
	tagName  = config['tagName'],
	doSingleQuote = (text.indexOf("'") >= 0),
	doDoubleQuote = (text.indexOf('"') >= 0),
	regexp, m, pos, chr;

// Do apostrophes ’ after a letter or at the beginning of a word or a couple of digits
if (doSingleQuote)
{
	// "/(?<=\\pL)'|(?<!\\S)'(?=\\pL|[0-9]{2})/uS"
	regexp = /[a-z]'|(?:^|\s)'(?=[a-z]|[0-9]{2})/gi;

	while (m = regexp.exec(text))
	{
		tag = addSelfClosingTag(tagName, m.index + m[0].indexOf("'"), 1);
		tag.setAttribute(attrName, "\u2019");

		// Give this tag a worse priority than default so that quote pairs take precedence
		tag.setSortPriority(10);
	}
}

// Do symbols found after a digit:
//  - apostrophe ’ if it's followed by an "s" as in 80's
//  - prime ′ and double prime ″
//  - multiply sign × if it's followed by an optional space and another digit
if (doSingleQuote || doDoubleQuote || text.indexOf('x') >= 0)
{
	// '/[0-9](?:\'s|["\']? ?x(?= ?[0-9])|["\'])/S',
	regexp = /[0-9](?:'s|["']? ?x(?= ?[0-9])|["'])/g;

	while (m = regexp.exec(text))
	{
		// Test for a multiply sign at the end
		pos = m.index + m[0].length - 1;
		if (m[0].charAt(pos) === 'x')
		{
			addSelfClosingTag(tagName, pos, 1).setAttribute(attrName, "\u0215");
		}

		// Test for a apostrophe/prime right after the digit
		c = m[0].charAt(1);
		if (c === "'" || c === '"')
		{
			pos = 1 + m.index;

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

			addSelfClosingTag(tagName, pos, 1).setAttribute(attrName, chr);
		}
	}
}

// Do quote pairs ‘’ and “” -- must be done separately to handle nesting
function captureQuotePairs(q, regexp, leftQuote, rightQuote)
{
	while (m = regexp.exec(text))
	{
		var left  = addSelfClosingTag(tagName, m.index + m[0].indexOf(q), 1),
			right = addSelfClosingTag(tagName, m.index + m[0].length - 1, 1);

		left.setAttribute(attrName, leftQuote);
		right.setAttribute(attrName, rightQuote);

		// Cascade left tag's invalidation to the right so that if we skip the left quote,
		// the right quote is left untouched
		left.cascadeInvalidationTo(right);
	}
}
if (doSingleQuote)
{
	// "/(?<![0-9\\pL])'[^'\\n]+'(?![0-9\\pL])/uS"
	captureQuotePairs("'", /(?:^|\W)'.+?'(?!\w)/g, "\u2018", "\u2019");
}
if (doDoubleQuote)
{
	// '/(?<![0-9\\pL])"[^"\\n]+"(?![0-9\\pL])/uS'
	captureQuotePairs('"', /(?:^|\W)".+?"(?!\w)/g, "\u201c", "\u201d");
}

// Do en dash –, em dash — and ellipsis …
if (text.indexOf('...') >= 0
 || text.indexOf('--')  >= 0)
{
	// '/[0-9](?:\'s|["\']? ?x(?= ?[0-9])|["\'])/S',
	regexp = /---?|\.\.\./g;

	while (m = regexp.exec(text))
	{
		pos = m.index;
		len = m[0].length;
		chr = {
			'--'  : "\u2013",
			'---' : "\u2014",
			'...' : "\u2026"
		}[m[0]];

		addSelfClosingTag(tagName, pos, len).setAttribute(attrName, chr);
	}
}

// Do symbols ©, ® and ™
if (text.indexOf('(') >= 0)
{
	// '/\\((?:c|r|tm)\\)/i'
	regexp = /\((?:c|r|tm)\)/gi;

	while (m = regexp.exec(text))
	{
		pos = m.index;
		len = m[0].length;
		chr = {
			'(c)'  : "\u00A9",
			'(r)'  : "\u00AE",
			'(tm)' : "\u2122"
		}[m[0].toLowerCase()];

		addSelfClosingTag(tagName, pos, len).setAttribute(attrName, chr);
	}
}