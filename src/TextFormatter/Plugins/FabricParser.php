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

		foreach ($matches['imagesAndLinks'] as $m)
		{
			$type = ($m[0][0][0] === '!') ? 'img' : 'link';
			$attr = (isset($m['attr']) && $m['attr'][1] > -1) ? substr($m['attr'][0], 1, -1) : null;

			$startTagPos = $m[0][1];

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

				++$startTagPos;

				$endTagPos = ($m['attr'][1] === -1)
				           ? $m['url'][1] - 1
				           : $m['attr'][1];

				$tags[] = array(
					'pos'  => $m['text'][1] + strlen($m['text'][0]),
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
					'len'   => $m[0][1],
					'name'  => 'IMG',
					'type'  => Parser::SELF_CLOSING_TAG,
					'attrs' => $attrs
				);
			}
		}

//print_r($matches);print_r($tags);exit;

		return $tags;
	}
}