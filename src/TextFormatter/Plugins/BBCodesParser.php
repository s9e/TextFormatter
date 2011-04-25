<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PluginParser;

class BBCodesParser extends PluginParser
{
	protected $tagsConfig;
	protected $bbcodesConfig;

	public function setUp()
	{
		$this->tagsConfig    = $this->parser->getTagsConfig();
		$this->bbcodesConfig = $this->config['bbcodesConfig'];
	}

	public function getTags($text, array $matches)
	{
		$tags = array();

		$textLen = strlen($text);

		foreach ($matches as $m)
		{
			$bbcodeName = strtoupper($m[1][0]);

			if (!isset($this->bbcodesConfig[$bbcodeName]))
			{
				// Not a known BBCode
				continue;
			}

			$bbcodeConfig = $this->bbcodesConfig[$bbcodeName];
			$tagName      = $bbcodeConfig['tagName'];
			$tagConfig    = $this->tagsConfig[$tagName];

			/**
			* @var Position of the first character of current BBCode, which should be a [
			*/
			$lpos = $m[0][1];

			/**
			* @var Position of the last character of current BBCode, starts as the position of
			*      the =, ] or : char, then moves to the right as the BBCode is parsed
			*/
			$rpos = $lpos + strlen($m[0][0]);

			/**
			* @var Attributes parsed from the text
			*/
			$attrs = array();

			/**
			* Check for BBCode suffix
			*
			* Used to skip the parsing of closing BBCodes, e.g.
			*   [code:1][code]type your code here[/code][/code:1]
			*
			*/
			if ($text[$rpos] === ':')
			{
				/**
				* [code:1] or [/code:1]
				* $suffix = ':1'
				*/
				$spn     = strspn($text, '1234567890', 1 + $rpos);
				$suffix  = substr($text, $rpos, 1 + $spn);
				$rpos   += 1 + $spn;
			}
			else
			{
				$suffix  = '';
			}

			if ($m[0][0][1] === '/')
			{
				if ($text[$rpos] !== ']')
				{
					$this->parser->log('warning', array(
						'pos'    => $rpos,
						'msg'    => 'Unexpected character: expected $1%s found $2%s',
						'params' => array(']', $text[$rpos])
					));
					continue;
				}

				$type = Parser::END_TAG;
			}
			else
			{
				$type       = Parser::START_TAG;
				$wellFormed = false;
				$attrName   = null;

				if ($text[$rpos] === '=')
				{
					/**
					* [quote=
					*
					* Set the default param. If there's no default param, we issue a warning and
					* reuse the BBCode's name instead
					*/
					if (isset($bbcodeConfig['defaultAttr']))
					{
						$attrName = $bbcodeConfig['defaultAttr'];
					}
					else
					{
						$attrName = strtolower($bbcodeName);

						$this->parser->log('debug', array(
							'pos'    => $rpos,
							'msg'    => 'BBCode %1$s does not have a default attribute, using BBCode name as attribute name',
							'params' => array($bbcodeName)
						));
					}

					++$rpos;
				}

				while ($rpos < $textLen)
				{
					$c = $text[$rpos];

					if ($c === ']' || $c === '/')
					{
						/**
						* We're closing this tag
						*/
						if (isset($attrName))
						{
							/**
							* [quote=]
							* [quote username=]
							*/
							$this->parser->log('warning', array(
								'pos'    => $rpos,
								'msg'    => 'Unexpected character %s',
								'params' => array($c)
							));
							continue 2;
						}

						if ($c === '/')
						{
							/**
							* Self-closing tag, e.g. [foo/]
							*/
							$type = Parser::SELF_CLOSING_TAG;
							++$rpos;

							if ($rpos === $textLen)
							{
								// text ends with [some tag/
								continue 2;
							}

							$c = $text[$rpos];
							if ($c !== ']')
							{
								$this->parser->log('warning', array(
									'pos'    => $rpos,
									'msg'    => 'Unexpected character: expected $1%s found $2%s',
									'params' => array(']', $c)
								));
								continue 2;
							}
						}

						$wellFormed = true;
						break;
					}

					if ($c === ' ')
					{
						++$rpos;
						continue;
					}

					if (!isset($attrName))
					{
						/**
						* Capture the attribute name
						*/
						$spn = strspn($text, 'abcdefghijklmnopqrstuvwxyz_0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', $rpos);

						if (!$spn)
						{
							$this->parser->log('warning', array(
								'pos'    => $rpos,
								'msg'    => 'Unexpected character %s',
								'params' => array($c)
							));
							continue 2;
						}

						if ($rpos + $spn >= $textLen)
						{
							$this->parser->log('debug', array(
								'pos' => $rpos,
								'msg' => 'Attribute name seems to extend till the end of text'
							));
							continue 2;
						}

						$attrName = strtolower(substr($text, $rpos, $spn));
						$rpos += $spn;

						if ($text[$rpos] !== '=')
						{
							$this->parser->log('debug', array(
								'pos'    => $rpos,
								'msg'    => 'Unexpected character: expected $1%s found $2%s',
								'params' => array('=', $text[$rpos])
							));
							continue 2;
						}

						++$rpos;
						continue;
					}

					if ($c === '"' || $c === "'")
					{
						$valuePos = $rpos + 1;

						while (++$rpos < $textLen)
						{
							$rpos = strpos($text, $c, $rpos);

							if ($rpos === false)
							{
								/**
								* No matching quote, apparently that string never ends...
								*/
								$this->parser->log('error', array(
									'pos' => $valuePos - 1,
									'msg' => 'Could not find matching quote'
								));
								continue 3;
							}

							if ($text[$rpos - 1] === '\\')
							{
								$n = 1;
								do
								{
									++$n;
								}
								while ($text[$rpos - $n] === '\\');

								if ($n % 2 === 0)
								{
									continue;
								}
							}

							break;
						}

						$value = stripslashes(substr($text, $valuePos, $rpos - $valuePos));

						// Skip past the closing quote
						++$rpos;
					}
					else
					{
						$spn   = strcspn($text, "] \n\r", $rpos);
						$value = substr($text, $rpos, $spn);

						$rpos += $spn;
					}

					$attrs[$attrName] = $value;
					unset($attrName, $value);
				}

				if (!$wellFormed)
				{
					continue;
				}

				$usesContent = false;

				if ($type === Parser::START_TAG
				 && isset($bbcodeConfig['contentAttr'])
				 && !isset($attrs[$bbcodeConfig['contentAttr']]))
				{
					/**
					* Capture the content of that tag and use it as attribute value
					*
					* @todo insert the corresponding closing tag now, to ensure that we captured
					*       exactly what will end up being this tag pair's content. Would make a
					*       difference in [a][b="[/a]"][/b][/a]
					*
					* @todo perhaps disable all BBCodes when the content is used as param? how?
					*/
					$pos = stripos($text, '[/' . $bbcodeName . $suffix . ']', $rpos);

					if ($pos)
					{
						$attrs[$bbcodeConfig['contentAttr']]
							= substr($text, 1 + $rpos, $pos - (1 + $rpos));

						$usesContent = true;
					}
				}
			}

			if ($type === Parser::START_TAG
			 && !$usesContent
			 && !empty($bbcodeConfig['autoClose']))
			{
				$endTag = '[/' . $bbcodeName . $suffix . ']';

				/**
				* Make sure that the start tag isn't immediately followed by an endtag
				*/
				if (strtoupper(substr($text, 1 + $rpos, strlen($endTag))) !== $endTag)
				{
					$type |= Parser::END_TAG;
				}
			}

			$tags[] = array(
				'name'   => $tagName,
				'pos'    => $lpos,
				'len'    => $rpos + 1 - $lpos,
				'type'   => $type,
				'suffix' => $suffix,
				'attrs'  => $attrs
			);
		}

		return $tags;
	}
}