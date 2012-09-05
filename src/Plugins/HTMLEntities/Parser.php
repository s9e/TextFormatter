<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\PluginParser;

class HTMLEntitiesParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		foreach ($matches as $m)
		{
			if (isset($this->config['disabled'][$m[0][0]]))
			{
				continue;
			}

			$char = html_entity_decode($m[0][0], ENT_QUOTES, 'UTF-8');

			if ($char === $m[0][0])
			{
				continue;
			}

			$tags[] = array(
				'pos'   => $m[0][1],
				'type'  => Parser::SELF_CLOSING_TAG,
				'name'  => $this->config['tagName'],
				'len'   => strlen($m[0][0]),
				'attrs' => array($this->config['attrName'] => $char)
			);
		}

		return $tags;
	}
}