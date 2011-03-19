<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter\Plugins;

use s9e\Toolkit\TextFormatter\Parser,
    s9e\Toolkit\TextFormatter\PluginParser;

class CensorParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		$tagName  = $this->config['tagName'];
		$attrName = $this->config['attrName'];

		foreach ($matches as $k => $_matches)
		{
			$replacements = (isset($this->config['replacements'][$k]))
			              ? $this->config['replacements'][$k]
			              : array();

			foreach ($_matches as $m)
			{
				$tag = array(
					'pos'  => $m[0][1],
					'name' => $tagName,
					'type' => Parser::SELF_CLOSING_TAG,
					'len'  => strlen($m[0][0])
				);

				foreach ($replacements as $mask => $replacement)
				{
					if (preg_match($mask, $m[0][0]))
					{
						$tag['attrs'][$attrName] = $replacement;
						break;
					}
				}

				$tags[] = $tag;
			}
		}

		return $tags;
	}
}