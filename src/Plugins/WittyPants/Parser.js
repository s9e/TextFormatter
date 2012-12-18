function parse(text)
{
	var attrName = config.attrName,
		tagName  = config.tagName,
		regexp,
		m,
		c,
		pos,
		char;

	// Do apostrophes ’ after a letter or at the beginning of a word or a couple of digits
	regexp = /[a-z]'|(?:^|\s)'(?=\w|[0-9]{2})|[0-9]'(?=s)/gi;
	while (m = regexp.exec(text))
	{
		addSelfClosingTag(tagName, m.index + m[0].indexOf("'"), 1).setAttribute(attrName, "\u2019");
	}

	// Do symbols found after a digit:
	//  - apostrophe ’ if it's followed by an "s" as in 80's
	//  - prime ′ and double prime ″
	//  - multiply sign × if it's followed by an optional space and another digit
	regexp = /[0-9](?:\'s|["\']? ?x(?= ?[0-9])|["\'])/gi
	while (m = regexp.exec(text))
	{
		// Test for a multiply sign
		if (m[0].substr(-1) === 'x')
		{
			pos  = m.index + m[0].length - 1;
			char = "\u00d7";

			addSelfClosingTag(tagName, pos, 1)->setAttribute(attrName, char);
		}

		// Test for a prime right after the digit
		c = m[0].charAt(1);
		if (c === "'" || c === '"')
		{
			pos  = m.index + 1;
			char = (c === "'") ? "\u2019" : "\u2033";

			addSelfClosingTag(tagName, pos, 1)->setAttribute(attrName, char);
		}
	}

	// Do quote pairs ‘’ and “”
	regexp = /(?:^|\W)(["'])(?:.+?)\1(?!\\w)/g
	while (m = regexp.exec(text))
	{
		var left  = addSelfClosingTag(tagName, m.index + m[0].char, 1);
		$right = $this->parser->addSelfClosingTag($tagName, $m[1] + strlen($m[0]) - 1, 1);

		$left->setAttribute($attrName, ($m[0][0] === '"') ? "\xE2\x80\x9C" : "\xE2\x80\x98");
		$right->setAttribute($attrName, ($m[0][0] === '"') ? "\xE2\x80\x9D" : "\xE2\x80\x99");

		// Cascade left tag's invalidation to the right so that if we skip the left quote, the
		// right quote is left untouched
		$left->cascadeInvalidationTo($right);
	}

	// Do en dash –, em dash — and ellipsis …
	preg_match_all(
		'#(?:---?|\\.\\.\\.)#S',
		$text,
		$matches,
		PREG_OFFSET_CAPTURE
	);
	$chars = array(
		'--'  => "\xE2\x80\x93",
		'---' => "\xE2\x80\x94",
		'...' => "\xE2\x80\xA6"
	);
	foreach ($matches[0] as $m)
	{
		$pos  = $m[1];
		$len  = strlen($m[0]);
		$char = $chars[$m[0]];

		$this->parser->addSelfClosingTag($tagName, $pos, $len)->setAttribute($attrName, $char);
	}

	// Do symbols ©, ® and ™
	preg_match_all(
		'#\\((?:c|r|tm)\\)#i',
		$text,
		$matches,
		PREG_OFFSET_CAPTURE
	);
	$chars = array(
		'(c)'  => "\xC2\xA9",
		'(r)'  => "\xC2\xAE",
		'(tm)' => "\xE2\x84\xA2"
	);
	foreach ($matches[0] as $m)
	{
		$pos  = $m[1];
		$len  = strlen($m[0]);
		$char = $chars[strtr($m[0][0], 'CMRT', 'cmrt')];

		$this->parser->addSelfClosingTag($tagName, $pos, $len)->setAttribute($attrName, $char);
	}
}
}