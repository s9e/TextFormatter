<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PluginParser;

class FabricParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		foreach ($matches['blockModifiers'] as $m)
		{
		}

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

		foreach ($matches['phraseModifiers'] as $m)
		{
			$tags[] = array(
				'pos'   => $m[0][1],
				'len'   => strlen($m[1][0]),
				'type'  => Parser::START_TAG,
				'name'  => $tagNames[$m[1][0]]
			);

			$tags[] = array(
				'pos'   => $m[2][1],
				'len'   => strlen($m[2][0]),
				'type'  => Parser::END_TAG,
				'name'  => $tagNames[$m[2][0]]
			);
		}

		foreach ($matches['imagesAndLinks'] as $m)
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

		return $tags;
	}
}