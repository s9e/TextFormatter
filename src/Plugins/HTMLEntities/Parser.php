<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\HTMLEntities;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$tagName  = $this->config['tagName'];
		$attrName = $this->config['attrName'];

		foreach ($matches as $m)
		{
			$entity = $m[0][0];
			$chr    = html_entity_decode($entity, ENT_HTML5 | ENT_QUOTES, 'UTF-8');

			if ($chr === $entity || ord($chr) < 32)
			{
				// If the entity was not decoded, we assume it's not valid and we ignore it.
				// Same thing if it's a control character
				continue;
			}

			$this->parser->addSelfClosingTag($tagName, $m[0][1], strlen($entity))->setAttribute($attrName, $chr);
		}
	}
}