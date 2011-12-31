<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Parser,
    s9e\TextFormatter\PluginParser;

class FabricParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		$this->getBlockModifiersTags($tags, $text, $matches['blockModifiers']);
		$this->getPhraseModifiersTags($tags, $text, $matches['phraseModifiers']);
		$this->getImagesAndLinksTags($tags, $text, $matches['imagesAndLinks']);
		$this->getAcronymsTags($tags, $text, $matches['acronyms']);

		return $tags;
	}

	protected function getAcronymsTags(&$tags, $text, array $matches)
	{
		foreach ($matches as $m)
		{
			$tags[] = array(
				'pos'   => $m[0][1],
				'len'   => 0,
				'type'  => Parser::START_TAG,
				'name'  => 'ACRONYM',
				'attrs' => array('title' => $m[2][0])
			);

			$tags[] = array(
				'pos'   => $m[2][1] - 1,
				'len'   => strlen($m[2][0]) + 2,
				'type'  => Parser::END_TAG,
				'name'  => 'ACRONYM'
			);
		}
	}

	protected function getBlockModifiersTags(&$tags, $text, array $matches)
	{
	}

	protected function getImagesAndLinksTags(&$tags, $text, array $matches)
	{
		foreach ($matches as $m)
		{
			$type = ($m[0][0][0] === '!') ? 'img' : 'link';
			$attr = (isset($m['attr']) && $m['attr'][1] > -1) ? substr($m['attr'][0], 1, -1) : null;

			$startTagPos = $m[0][1];
			$endTagPos   = strlen($m[0][0]);

			if (isset($m['url']))
			{
				$attrs = array(
					// Remove the colon at the start of the capture
					'url' => substr($m['url'][0], 1)
				);

				if (isset($attr) && $type === 'link')
				{
					$attrs['title'] = $attr;
				}

				$tags[] = array(
					'pos'   => $startTagPos,
					'len'   => 1,
					'name'  => 'URL',
					'type'  => Parser::START_TAG,
					'attrs' => $attrs
				);

				/**
				* The first character belongs to this tag, therefore if there's an IMG tag it will
				* have to start right after it
				*/
				++$startTagPos;

				/**
				* If this is a link (no image) then <URL>'s end tag starts right after $m['text']
				* and includes the link's title if applicable. If this is an image, it starts with
				* the colon at the start of $m['url']
				*/
				$endTagPos = ($type === 'link')
				           ? $m['text'][1] + strlen($m['text'][0])
				           : $m['url'][1];

				$tags[] = array(
					'pos'  => $endTagPos,
					'len'  => strlen($m[0][0]) - $endTagPos,
					'name' => 'URL',
					'type' => Parser::END_TAG
				);
			}
			elseif ($type === 'link')
			{
				// A link with no URL is not a link, it's just text within double quotes
				continue;
			}

			if ($type === 'img')
			{
				$attrs = array(
					'src' => $m['text'][0]
				);

				if (isset($attr))
				{
					$attrs['alt']   = $attr;
					$attrs['title'] = $attr;
				}

				$tags[] = array(
					'pos'   => $startTagPos,
					'len'   => $endTagPos - $startTagPos,
					'name'  => 'IMG',
					'type'  => Parser::SELF_CLOSING_TAG,
					'attrs' => $attrs
				);
			}
		}
	}

	protected function getPhraseModifiersTags(&$tags, $text, array $matches)
	{
		$tagNames = array(
			'_'  => 'EM',
			'__' => 'I',
			'*'  => 'STRONG',
			'**' => 'B',
			'??' => 'CITE',
			'-'  => 'DEL',
			'+'  => 'INS',
			'^'  => 'SUPER',
			'~'  => 'SUB',
			'@'  => 'CODE',
			'%'  => 'SPAN',
			'==' => 'NOPARSE'
		);

		foreach ($matches as $m)
		{
			$tagName = $tagNames[$m[1][0]];
			$startTagLen = strlen($m[1][0]);
			$attrs = array();

			if ($m[0][0][1] === '(')
			{
				/**
				* This phrase modifier has an attribute, e.g. %(class)text% or @(stx)text@
				*/
				if ($pos = strpos($m[0][0], ')', 3))
				{
					$attr = substr($m[0][0], 2, $pos - 2);

					if ($tagName === 'SPAN')
					{
						$attrs['class'] = $attr;
					}
					elseif ($tagName === 'CODE')
					{
						$attrs['stx'] = $attr;
					}

					$startTagLen += 2 + strlen($attr);
				}
			}

			$tags[] = array(
				'pos'   => $m[0][1],
				'len'   => $startTagLen,
				'type'  => Parser::START_TAG,
				'name'  => $tagName,
				'attrs' => $attrs
			);

			$tags[] = array(
				'pos'   => $m[2][1],
				'len'   => strlen($m[2][0]),
				'type'  => Parser::END_TAG,
				'name'  => $tagNames[$m[2][0]]
			);
		}
	}
}