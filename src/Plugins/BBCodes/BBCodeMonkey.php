<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use DOMDocument;
use DOMXPath;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Tag;

abstract class BBCodeMonkey
{
	/**
	* @var array List of pre- and post- filters that are explicitly allowed in BBCode definitions.
	*            We use a whitelist approach because there are so many different risky callbacks
	*            that it would be too easy to let something dangerous slip by, e.g.: unlink,
	*            system, etc...
	*/
	protected static $allowedFilters = array(
		'addslashes',
		'dechex',
		'intval',
		'json_encode',
		'ltrim',
		'mb_strtolower',
		'mb_strtoupper',
		'rawurlencode',
		'rtrim',
		'str_rot13',
		'stripslashes',
		'strrev',
		'strtolower',
		'strtotime',
		'strtoupper',
		'trim',
		'ucfirst',
		'ucwords',
		'urlencode'
	);

	/**
	* @var array Regexps used in the named subpatterns generated automatically for composite
	*            attributes. For instance, "foo={NUMBER},{NUMBER}" will be transformed into
	*            'foo={PARSE=#^(?<foo0>\\d+),(?<foo1>\\d+)$#D}'
	*/
	protected static $tokenRegexp = array(
		'COLOR'      => '[a-zA-Z]+|#[0-9a-fA-F]+',
		'EMAIL'      => '[^@]+@.+?',
		'FLOAT'      => '(?:0|-?[1-9]\\d*)(?:\\.\\d+)?(?:e[1-9]\\d*)?',
		'ID'         => '[-a-zA-Z0-9_]+',
		'IDENTIFIER' => '[-a-zA-Z0-9_]+',
		'INT'        => '0|-?[1-9]\\d*',
		'INTEGER'    => '0|-?[1-9]\\d*',
		'NUMBER'     => '\\d+',
		'RANGE'      => '\\d+',
		'SIMPLETEXT' => '[-a-zA-Z0-9+.,_ ]+',
		'UINT'       => '0|[1-9]\\d*'
	);

	/**
	* Create a BBCode based on its reference usage
	*
	* @param  string $usage BBCode usage, e.g. [B]{TEXT}[/b]
	* @return array
	*/
	public static function parse($usage)
	{
		$tag    = new Tag;
		$bbcode = new BBCode;

		// This is the config we will return
		$config = array(
			'tag'              => $tag,
			'bbcode'           => $bbcode,
			'passthroughToken' => null
		);

		$regexp = '#^'
		        // [BBCODE
		        . '\\[(?<bbcodeName>.+?)'
		        // ={TOKEN}
		        . '(?<defaultAttribute>=\\S+?)?'
		        // foo={TOKEN} bar={TOKEN1},{TOKEN2}
		        . '(?<attributes>(?:\\s+[^=]+=\\S+?)*?)?'
		        // ] or /] or ]{TOKEN}[/BBCODE]
		        . '(?:\\s*/?\\]|\\]\\s*(?<content>\\S+)?\\s*(?<endTag>\\[/\\1]))'
		        . '$#i';

		if (!preg_match($regexp, trim($usage), $m))
		{
			throw new InvalidArgumentException('Cannot interpret the BBCode definition');
		}

		// Save the BBCode's name
		$config['name'] = BBCode::normalizeName($m['bbcodeName']);

		// Prepare the attributes definition, e.g. "foo={BAR}"
		$attributes = $m['attributes'];

		// If there's a default attribute, we prepend it to the list using the BBCode's name as
		// attribute name
		if (!empty($m['defaultAttribute']))
		{
			$attributes = $m['bbcodeName'] . $m['defaultAttribute'] . $attributes;
		}

		// Append the content token to the attributes list under the name "content" if it's anything
		// but raw {TEXT}
		if (!empty($m['content']))
		{
			if (preg_match('#^\\{TEXT[0-9]*\\}$#D', $m['content']))
			{
				$config['passthroughToken'] = substr($m['content'], 1, -1);
			}
			else
			{
				$attributes .= ' content=' . $m['content'];
				$bbcode->contentAttributes[] = 'content';
			}
		}

		// Add the attributes and get the token translation table
		$tokens = self::addAttributes($attributes, $bbcode, $tag);

		// Test whether the passthrough token is used for something else, in which case we need
		// to unset it
		if (isset($tokens[$config['passthroughToken']]))
		{
			$config['passthroughToken'] = null;
		}

		// Add the list of known (and only the known) tokens to the config
		$config['tokens'] = array_filter($tokens);

		return $config;
	}

	/**
	* Replace tokens in a template
	*
	* @param  string $template         Original template
	* @param  array  $tokens           Array of [tokenId => attrName]
	* @param  string $passthroughToken Token ID of the token that represents the BBCode's contents
	* @return string                   Processed template
	*/
	public static function replaceTokens($template, array $tokens, $passthroughToken)
	{
		if ($template === '')
		{
			return $template;
		}

		$dom   = self::loadTemplate($template);
		$xpath = new DOMXPath($dom);

		// Replace tokens in attributes
		foreach ($xpath->query('//@*') as $attr)
		{
			$attr->value = htmlspecialchars(preg_replace_callback(
				'#\\{[A-Z]+[0-9]*?\\}#',
				function ($m) use ($tokens, $passthroughToken)
				{
					$tokenId = substr($m[0], 1, -1);

					if (!isset($tokens[$tokenId]))
					{
						if ($tokenId === $passthroughToken)
						{
							// Use substring() to exclude the <st/> and <et/> children
							return '{substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))}';
						}

						throw new RuntimeException('Token {' . $tokenId . '} is ambiguous or undefined');
					}

					return '{@' . $tokens[$tokenId] . '}';
				},
				$attr->value
			));
		}

		// Replace tokens in text nodes
		foreach ($xpath->query('//text()') as $node)
		{
			preg_match_all(
				'#\\{[A-Z]+[0-9]*?\\}#',
				$node->textContent,
				$matches,
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			if (empty($matches))
			{
				continue;
			}

			// Grab the node's parent so that we can rebuild the text with added variables right
			// before the node, using DOM's insertBefore(). Technically, it would make more sense
			// to create a document fragment, append nodes then replace the node with the fragment
			// but it leads to namespace redeclarations, which looks ugly
			$parentNode = $node->parentNode;

			$lastPos = 0;
			foreach ($matches as $m)
			{
				$tokenId = substr($m[0][0], 1, -1);
				$pos     = $m[0][1];

				if ($pos > ($lastPos + 1))
				{
					$parentNode->insertBefore(
						$dom->createTextNode(
							substr($node->textContent, $lastPos, $pos - $lastPos)
						),
						$node
					);
				}
				$lastPos = $pos + strlen($m[0][0]);

				if (isset($tokens[$tokenId]))
				{
					$parentNode
						->insertBefore(
							$dom->createElementNS(
								'http://www.w3.org/1999/XSL/Transform',
								'xsl:value-of'
							),
							$node
						)
						->setAttribute('select', '@' . $tokens[$tokenId]);
				}
				elseif ($tokenId === $passthroughToken)
				{
					$parentNode->insertBefore(
						$dom->createElementNS(
							'http://www.w3.org/1999/XSL/Transform',
							'xsl:apply-templates'
						),
						$node
					);
				}
				else
				{
					throw new RuntimeException('Token {' . $tokenId . '} is ambiguous or undefined');
				}
			}

			// Append the rest of the text
			$text = substr($node->textContent, $lastPos);
			if ($text > '')
			{
				$parentNode->insertBefore($dom->createTextNode($text), $node);
			}

			// Now remove the old text node
			$parentNode->removeChild($node);
		}

		// Now dump our temporary node as XML and remove the root node's markup
		$xml = $dom->saveXML($dom->documentElement);

		$lpos = 1 + strpos($xml, '>');
		$rpos = strrpos($xml, '<');

		$template = substr($xml, $lpos, $rpos - $lpos);

		if ($template === false)
		{
			throw new RuntimeException('Invalid template');
		}

		return $template;
	}

	/**
	* Attempt to load a template with DOM, first as XML then as HTML as a fallback
	*
	* @param  string      $template
	* @return DOMDocument
	*/
	protected static function loadTemplate($template)
	{
		$dom = new DOMDocument;

		// Generate a random tag name so that the user cannot inject stuff outside of that template.
		// For instance, if the tag was <t>, one could input </t><xsl:evil-stuff/><t>
		$t = 't' . md5(microtime(true) . mt_rand());

		// First try as XML
		$xml = '<?xml version="1.0" encoding="utf-8" ?><' . $t . ' xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . $template . '</' . $t . '>';

		try
		{
			$useErrors = libxml_use_internal_errors(true);
			$success = $dom->loadXML($xml);
		}
		catch (Exception $e)
		{
		}

		libxml_use_internal_errors($useErrors);

		if ($success)
		{
			// Success!
			return $dom;
		}

		// Fall back to loading it inside a div, as HTML
		$html = '<html><body><div id="' . $t . '">' . $template . '</div></body></html>';

		$useErrors = libxml_use_internal_errors(true);
		$success = $dom->loadHTML($html);
		libxml_use_internal_errors($useErrors);

		// @codeCoverageIgnoreStart
		if (!$success)
		{
			$error = libxml_get_last_error();
			throw new InvalidArgumentException('Invalid HTML in template - error was: ' . $error->message);
		}
		// @codeCoverageIgnoreEnd

		// Now dump the thing as XML and reload it to ensure we don't have to worry about internal
		// shenanigans
		$xml = $dom->saveXML($dom->getElementById($t));

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		return $dom;
	}

	/**
	* Parse a string of attribute definitions and add the attributes/options to the tag/BBCode
	*
	* Attributes come in two forms. Most commonly, in the form of a single token, e.g.
	*   [a href={URL} title={TEXT}]
	*
	* Sometimes, however, we need to parse more than one single token. For instance, the phpBB
	* [FLASH] BBCode uses two tokens separated by a comma:
	*   [flash={NUMBER},{NUMBER}]{URL}[/flash]
	*
	* In addition, some custom BBCodes circulating for phpBB use a combination of token and static
	* text such as:
	*   [youtube]http://www.youtube.com/watch?v={SIMPLETEXT}[/youtube]
	*
	* Any attribute that is not a single token is implemented as an attribute preprocessor, with
	* each token generating a matching attribute. Tentatively, those  of those attributes are
	* created by taking the attribute preprocessor's name and appending a unique number counting the
	* number of created attributes. In the [FLASH] example above, an attribute preprocessor named
	* "flash" would be created as well as two attributes named "flash0" and "flash1" respectively.
	*
	* @link https://www.phpbb.com/community/viewtopic.php?f=46&t=2127991
	* @link https://www.phpbb.com/community/viewtopic.php?f=46&t=579376
	*
	* @param  string $str    Attributes definitions, e.g. "foo={INT} bar={TEXT}"
	* @param  BBCode $bbcode Owner BBCode
	* @param  Tag    $tag    Owner tag
	* @return array          Array of [token id => attribute name] where FALSE in place of the
	*                        name indicates that the token is ambiguous (e.g. used multiple times)
	*/
	protected static function addAttributes($str, BBCode $bbcode, Tag $tag)
	{
		/**
		* @var array List of composites' tokens. Each element is composed of an attribute name, the
		*            composite's definition and an array of tokens
		*/
		$composites = array();

		/**
		* @var array Map of [tokenId => attrName]. If the same token is used in multiple attributes
		*            it is set to FALSE
		*/
		$tokenAttribute = array();

		foreach (preg_split('#\\s+#', trim($str), -1, PREG_SPLIT_NO_EMPTY) as $k => $pair)
		{
			$pos = strpos($pair, '=');

			// @codeCoverageIgnoreStart
			if ($pos === false)
			{
				// NOTE: the regexp used makes this code impossible to reach, it's left there as
				//       a failsafe
				throw new RuntimeException("Could not find = in '" . $pair . "'");
			}
			// @codeCoverageIgnoreEnd

			// The name at the left of the equal sign is the attribute's or attribute preprocessor's
			// name, the rest is their definition
			$attrName   = trim(substr($pair, 0, $pos));
			$definition = trim(substr($pair, 1 + $pos));

			// The first attribute defined is set as default
			if (!isset($bbcode->defaultAttribute))
			{
				$bbcode->defaultAttribute = $attrName;
			}

			// Parse the tokens in that definition
			$tokens = self::parseTokens($definition);

			if (empty($tokens))
			{
				throw new RuntimeException('No tokens found in ' . $attrName . "'s definition");
			}

			// Test whether this attribute has one single all-encompassing token
			if ($tokens[0]['content'] === $definition)
			{
				$token = $tokens[0];

				if ($token['type'] === 'PARSE')
				{
					$tag->attributePreprocessors->add($attrName, $token['regexp']);
				}
				elseif (isset($tag->attributes[$attrName]))
				{
					throw new RuntimeException("Attribute '" . $attrName . "' is declared twice");
				}
				else
				{
					// Remove the "useContent" option and add the attribute's name to the list of
					// attributes to use this BBCode's content
					if (!empty($token['options']['useContent']))
					{
						$bbcode->contentAttributes[] = $attrName;
					}
					unset($token['options']['useContent']);

					// Add the attribute
					$tag->attributes[$attrName] = self::generateAttribute($token);

					// Record the token ID if applicable
					$tokenId = $token['id'];
					$tokenAttribute[$tokenId] = (isset($tokenAttribute[$tokenId]))
					                          ? false
					                          : $attrName;
				}
			}
			else
			{
				$composites[] = array($attrName, $definition, $tokens);
			}
		}

		foreach ($composites as $composite)
		{
			list($attrName, $definition, $tokens) = $composite;

			$regexp  = '/^';
			$lastPos = 0;

			$usedTokens = array();

			foreach ($tokens as $token)
			{
				$tokenId   = $token['id'];
				$tokenType = $token['type'];

				if ($tokenType === 'PARSE')
				{
					// Disallow {PARSE} tokens because attribute preprocessors cannot feed into
					// other attribute preprocessors
					throw new RuntimeException('{PARSE} tokens can only be used has the sole content of an attribute');
				}

				// Ensure that tokens are only used once per definition so we don't have multiple
				// subpatterns using the same name
				if (isset($usedTokens[$tokenId]))
				{
					throw new RuntimeException('Token {' . $tokenId . '} used multiple times in attribute ' . $attrName . "'s definition");
				}
				$usedTokens[$tokenId] = 1;

				// Find the attribute name associated with this token, or create an attribute
				// otherwise
				if (isset($tokenAttribute[$tokenId]))
				{
					$matchName = $tokenAttribute[$tokenId];

					if ($matchName === false)
					{
						throw new RuntimeException('Token {' . $tokenId . "} used in attribute '" . $attrName . "' is ambiguous");
					}
				}
				else
				{
					// The name of the named subpattern and the corresponding attribute is based on
					// the attribute preprocessor's name, with an incremented ID that ensures we
					// don't overwrite existing attributes
					$i = 0;
					do
					{
						$matchName = $attrName . $i;
						++$i;
					}
					while (isset($tag->attributes[$matchName]));

					// Create the attribute that corresponds to this subpattern
					$tag->attributes[$matchName] = new Attribute;
					$tag->attributes[$matchName]->filterChain->append('#' . strtolower($tokenType));

					// Record the attribute name associated with this token ID
					$tokenAttribute[$tokenId] = $matchName;
				}

				// Append the literal text between the last position and current position
				$regexp .= preg_quote(substr($definition, $lastPos, $token['pos'] - $lastPos), '/');

				// Grab the expression that corresponds to the token type, or use a catch-all
				// expression otherwise
				$expr = (isset(self::$tokenRegexp[$tokenType]))
				      ? self::$tokenRegexp[$tokenType]
				      : '.+?';

				// Append the named subpattern. Its name is made of the attribute preprocessor's
				// name and the subpattern's position
				$regexp .= '(?<' . $matchName . '>' . $expr . ')';

				// Update the last position
				$lastPos = $token['pos'] + strlen($token['content']);
			}

			// Append the literal text that follows the last token and finish the regexp
			$regexp .= preg_quote(substr($definition, $lastPos), '#') . '$/D';

			// Add the attribute preprocessor to the config
			$tag->attributePreprocessors->add($attrName, $regexp);
		}

		// Now create attributes generated from attribute preprocessors. For instance, preprocessor
		// #(?<width>\\d+),(?<height>\\d+)# will generate two attributes named "width" and height
		// with a regexp filter "#^(?:\\d+)$#D", unless they were explicitly defined otherwise
		$newAttributes = array();
		foreach ($tag->attributePreprocessors as $attributePreprocessor)
		{
			foreach ($attributePreprocessor->getAttributes() as $attrName => $regexp)
			{
				if (isset($tag->attributes[$attrName]))
				{
					// This attribute was already explicitly defined, nothing else to add
					continue;
				}

				if (isset($newAttributes[$attrName])
				 && $newAttributes[$attrName] !== $regexp)
				{
					throw new RuntimeException("Ambiguous attribute '" . $attrName . "' created using different regexps needs to be explicitly defined");
				}

				$newAttributes[$attrName] = $regexp;
			}
		}

		foreach ($newAttributes as $attrName => $regexp)
		{
			// Create the attribute using this regexp as filter
			$tag->attributes[$attrName] = new Attribute;
			$tag->attributes[$attrName]->filterChain->append('#regexp', array('regexp' => $regexp));
		}

		return $tokenAttribute;
	}

	/**
	* Parse and return all the tokens contained in a definition
	*
	* @param  string $definition
	* @return array
	*/
	protected static function parseTokens($definition)
	{
		// This regexp should match a regexp, including its delimiters and optionally the i, u
		// and/or i flags
		$regexpMatcher = '(?<regexp>(?<delim>.).*?(?<!\\\\)(?:\\\\\\\\)*+(?P=delim)[ius]*)';

		$tokenTypes = array(
			'choice' => 'CHOICE[0-9]*=(?<choices>.+?)',
			'regexp' => '(?:REGEXP[0-9]*|PARSE)=' . $regexpMatcher,
			'range'  => 'RAN(?:DOM|GE)[0-9]*=(?<min>-?[0-9]+),(?<max>-?[0-9]+)',
			'other'  => '[A-Z_]+[0-9]*'
		);

		// Capture the content of every token in that attribute's definition. Usually there will
		// only be one, as in "foo={URL}" but some older BBCodes use a form of composite
		// attributes such as [FLASH={NUMBER},{NUMBER}]
		preg_match_all(
			'#\\{(' . implode('|', $tokenTypes) . ')(?<options>(?:;[^;]*)*)\\}#',
			$definition,
			$matches,
			PREG_SET_ORDER | PREG_OFFSET_CAPTURE
		);

		$tokens = array();
		foreach ($matches as $m)
		{
			$token = array(
				'pos'     => $m[0][1],
				'content' => $m[0][0],
				'options' => array()
			);

			$head    = $m[1][0];
			$options = (isset($m['options'][0])) ? $m['options'][0] : '';

			$pos = strpos($head, '=');

			if ($pos === false)
			{
				$token['id']   = $head;
				$token['type'] = rtrim($token['id'], '0123456789');
			}
			else
			{
				$token['id']       = substr($head, 0, $pos);
				$token['type']     = rtrim($token['id'], '0123456789');

				// Copy the content of named subpatterns into the token's config
				foreach ($m as $k => $v)
				{
					if (!is_numeric($k) && $k !== 'delim' && $k !== 'options')
					{
						$token[$k] = $v[0];
					}
				}
			}

			// Parse the options
			foreach (preg_split('#;+#', $options, -1, PREG_SPLIT_NO_EMPTY) as $pair)
			{
				$pos = strpos($pair, '=');

				if ($pos === false)
				{
					// Options with no value are set to true, e.g. {FOO;useContent}
					$k = $pair;
					$v = true;
				}
				else
				{
					$k = substr($pair, 0, $pos);
					$v = substr($pair, 1 + $pos);
				}

				$token['options'][$k] = $v;
			}

			$tokens[] = $token;
		}

		return $tokens;
	}

	/**
	* Generate an attribute based on a token
	*
	* @param  array  $token  Token this attribute is based on
	* @return void
	*/
	protected static function generateAttribute(array $token)
	{
		$attribute = new Attribute;

		if (isset($token['options']['preFilter']))
		{
			self::appendFilters($attribute, $token['options']['preFilter']);
			unset($token['options']['preFilter']);
		}

		if ($token['type'] === 'REGEXP')
		{
			$attribute->filterChain->append('#regexp', array('regexp' => $token['regexp']));
		}
		elseif ($token['type'] === 'RANGE')
		{
			$attribute->filterChain->append('#range', array(
				'min' => $token['min'],
				'max' => $token['max']
			));
		}
		elseif ($token['type'] === 'RANDOM')
		{
			$attribute->generator = ProgrammableCallback::fromArray(array(
				'callback' => 'mt_rand',
				'params'   => array($token['min'], $token['max'])
			));
		}
		elseif ($token['type'] === 'CHOICE')
		{
			// Build a regexp from the list of choices then add a "#regexp" filter
			$regexp = RegexpBuilder::fromList(
				explode(',', $token['choices']),
				array('specialChars' => array('/' => '\\/'))
			);
			$regexp = '/^' . $regexp . '$/D';

			// Add the case-insensitive flag until specified otherwise
			if (empty($token['options']['caseSensitive']))
			{
				$regexp .= 'i';
			}
			unset($token['options']['caseSensitive']);

			// Add the Unicode flag if the regexp isn't purely ASCII
			if (!preg_match('#^[[:ascii:]]*$#D', $regexp))
			{
				$regexp .= 'u';
			}

			$attribute->filterChain->append('#regexp', array('regexp' => $regexp));
		}
		elseif ($token['type'] !== 'TEXT')
		{
			$attribute->filterChain->append('#' . strtolower($token['type']));
		}

		if (isset($token['options']['postFilter']))
		{
			self::appendFilters($attribute, $token['options']['postFilter']);
			unset($token['options']['postFilter']);
		}

		// Set the "required" option if "required" or "optional" is set, then remove
		// the "optional" option
		if (isset($token['options']['required']))
		{
			$token['options']['required'] = (bool) $token['options']['required'];
		}
		elseif (isset($token['options']['optional']))
		{
			$token['options']['required'] = !$token['options']['optional'];
		}
		unset($token['options']['optional']);

		foreach ($token['options'] as $k => $v)
		{
			$attribute->$k = $v;
		}

		return $attribute;
	}

	/**
	* Append a list of filters to an attribute's filterChain
	*
	* @param  Attribute $attribute
	* @param  string    $filters   List of filters, separated with commas
	* @return void
	*/
	protected static function appendFilters(Attribute $attribute, $filters)
	{
		foreach (preg_split('#\\s*,\\s*#', $filters) as $filter)
		{
			if (!in_array($filter, self::$allowedFilters, true))
			{
				throw new RuntimeException("Filter '" . $filter . "' is not allowed");
			}

			$attribute->filterChain->append($filter);
		}
	}
}