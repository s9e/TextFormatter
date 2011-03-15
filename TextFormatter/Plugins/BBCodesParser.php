<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PluginParser;

class EmoticonsParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		$bbcodes = $config['bbcodes'];
		$aliases = $config['aliases'];
		$textLen = strlen($text);

		foreach ($matches as $m)
		{
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

			$alias = strtoupper($m[1][0]);

			if (!isset($aliases[$alias]))
			{
				// Not a known BBCode or alias
				continue;
			}

			$bbcodeId = $aliases[$alias];
			$bbcode   = $bbcodes[$bbcodeId];
			$params   = array();

			if (!empty($bbcode['internal_use']))
			{
				/**
				* This is theorically impossible, as the regexp does not contain internal BBCodes.
				*/
				if ($m[0][0][1] !== '/')
				{
					/**
					* We only warn about starting tags, no need to raise 2 warnings per pair
					*/
					$msgs['warning'][] = array(
						'pos'    => $lpos,
						'msg'    => 'BBCode %s is for internal use only',
						'params' => array($bbcodeId)
					);
				}
				continue;
			}

			if ($m[0][0][1] === '/')
			{
				if ($text[$rpos] !== ']')
				{
					$msgs['warning'][] = array(
						'pos'    => $rpos,
						'msg'    => 'Unexpected character %s',
						'params' => array($text[$rpos])
					);
					continue;
				}

				$type = self::END_TAG;
			}
			else
			{
				$type       = self::START_TAG;
				$wellFormed = false;
				$param      = null;

				if ($text[$rpos] === '=')
				{
					/**
					* [quote=
					*
					* Set the default param. If there's no default param, we issue a warning and
					* reuse the BBCode's name instead
					*/
					if (isset($bbcode['defaultParam']))
					{
						$param = $bbcode['defaultParam'];
					}
					else
					{
						$param = strtolower($bbcodeId);

						$msgs['debug'][] = array(
							'pos'    => $rpos,
							'msg'    => "BBCode %s does not have a default param, using BBCode's name as param name",
							'params' => array($bbcodeId)
						);
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
						if (isset($param))
						{
							/**
							* [quote=]
							* [quote username=]
							*/
							$msgs['warning'][] = array(
								'pos'    => $rpos,
								'msg'    => 'Unexpected character %s',
								'params' => array($c)
							);
							continue 2;
						}

						if ($c === '/')
						{
							/**
							* Self-closing tag, e.g. [foo/]
							*/
							$type = self::SELF_CLOSING_TAG;
							++$rpos;

							if ($rpos === $textLen)
							{
								// text ends with [some tag/
								continue 2;
							}

							$c = $text[$rpos];
							if ($c !== ']')
							{
								$msgs['warning'][] = array(
									'pos'    => $rpos,
									'msg'    => 'Unexpected character: expected ] found %s',
									'params' => array($c)
								);
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

					if (!isset($param))
					{
						/**
						* Capture the param name
						*/
						$spn = strspn($text, 'abcdefghijklmnopqrstuvwxyz_0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', $rpos);

						if (!$spn)
						{
							$msgs['warning'][] = array(
								'pos'    => $rpos,
								'msg'    => 'Unexpected character %s',
								'params' => array($c)
							);
							continue 2;
						}

						if ($rpos + $spn >= $textLen)
						{
							$msgs['debug'][] = array(
								'pos' => $rpos,
								'msg' => 'Param name seems to extend till the end of $text'
							);
							continue 2;
						}

						$param = strtolower(substr($text, $rpos, $spn));
						$rpos += $spn;

						if ($text[$rpos] !== '=')
						{
							$msgs['warning'][] = array(
								'pos'    => $rpos,
								'msg'    => 'Unexpected character %s',
								'params' => array($text[$rpos])
							);
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
								$msgs['error'][] = array(
									'pos' => $valuePos - 1,
									'msg' => 'Could not find matching quote'
								);
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

					if (isset($bbcode['params'][$param]))
					{
						/**
						* We only keep params that exist in the BBCode's definition
						*/
						$params[$param] = $value;
					}

					unset($param, $value);
				}

				if (!$wellFormed)
				{
					continue;
				}

				$usesContent = false;

				if ($type === self::START_TAG
				 && isset($bbcode['defaultParam'])
				 && !isset($params[$bbcode['defaultParam']])
				 && !empty($bbcode['content_as_param']))
				{
					/**
					* Capture the content of that tag and use it as param
					*
					* @todo insert the corresponding closing tag now, to ensure that we captured
					*       exactly what will end up being this tag pair's content. Would make a
					*       difference in [a][b="[/a]"][/b][/a]
					*
					* @todo perhaps disable all BBCodes when the content is used as param? how?
					*/
					$pos = stripos($text, '[/' . $bbcodeId . $suffix . ']', $rpos);

					if ($pos)
					{
						$params[$bbcode['defaultParam']]
							= substr($text, 1 + $rpos, $pos - (1 + $rpos));

						$usesContent = true;
					}
				}
			}

			if ($type === self::START_TAG
			 && !$usesContent
			 && !empty($bbcode['auto_close']))
			{
				$endTag = '[/' . $bbcodeId . $suffix . ']';

				/**
				* Make sure that the start tag isn't immediately followed by an endtag
				*/
				if (strtoupper(substr($text, 1 + $rpos, strlen($endTag))) !== $endTag)
				{
					$type |= self::END_TAG;
				}
			}

			$tags[] = array(
				'name'   => $bbcodeId,
				'pos'    => $lpos,
				'len'    => $rpos + 1 - $lpos,
				'type'   => $type,
				'suffix' => $suffix,
				'params' => $params
			);
		}

		return $tags;
	}
}