<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\AbstractStaticUrlReplacer;

use s9e\TextFormatter\Plugins\ParserBase;

class AbstractParser extends ParserBase
{
	protected int $tagPriority = 0;

	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$tagName  = $this->config['tagName'];
		$attrName = $this->config['attrName'];
		$prio     = $this->tagPriority;
		foreach ($matches as $m)
		{
			$this->parser->addTagPair($tagName, $m[0][1], 0, $m[0][1] + strlen($m[0][0]), 0, $prio)
			             ->setAttribute($attrName, $m[0][0]);
		}
	}
}