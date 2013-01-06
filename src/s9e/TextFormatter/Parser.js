/**#@+
* Boolean rules bitfield
*/
/** @const */ var RULE_AUTO_CLOSE       =  1;
/** @const */ var RULE_AUTO_REOPEN      =  2;
/** @const */ var RULE_IGNORE_TEXT      =  4;
/** @const */ var RULE_IS_TRANSPARENT   =  8;
/** @const */ var RULE_NO_BR_CHILD      = 16;
/** @const */ var RULE_NO_BR_DESCENDANT = 32;
/** @const */ var RULE_TRIM_WHITESPACE  = 64;
/**#@-*/

/**
* @type {!Logger} This parser's logger
*/
var logger = new Logger;

/**
* @type {!Object} Variables registered for use in filters
*/
var registeredVars;

/**
* @type {!Object} Tags' config
* @const
*/
var tagsConfig;

/**
* @type {!string} Text being parsed
*/
var text;

/**
* @type {!number} Length of the text being parsed
*/
var textLen;

/**
* Get this parser's Logger instance
*
* @return {!Logger}
*/
function getLogger()
{
	return logger;
}

/**
* Parse a text
*
* @param  {!string} _text Text to parse
* @return {!string}       XML representation
*/
function parse(_text)
{
	reset(_text);
	executePluginParsers();
	sortTags();
	processTags();

	return output;
}

/**
* Reset the parser for a new parsing
*
* @param {!string} _text Text to be parsed
*/
function reset(_text)
{
	// Normalize CR/CRLF to LF, remove control characters that aren't allowed in XML
	_text = _text.replace(/\r\n?/g, "\n", _text);
	_text = _text.replace(/[\x00-\x08\x0B\x0C\x0E-\x1F]+/g, '', _text);

	context    = rootContext;
	currentFixingCost = 0;
	isRich     = false;
	namespaces = {};
	output     = '';
	text       = _text;
	textLen    = text.length;
	tagStack   = [];
}