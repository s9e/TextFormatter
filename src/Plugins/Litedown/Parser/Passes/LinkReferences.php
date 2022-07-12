<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

class LinkReferences extends AbstractPass
{
	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		if ($this->text->indexOf(']:') === false)
		{
			return;
		}

		$regexp = '/^\\x1A* {0,3}\\[([^\\x17\\]]+)\\]: *([^[\\s\\x17]+ *(?:"[^\\x17]*?"|\'[^\\x17]*?\'|\\([^\\x17)]*\\))?) *(?=$|\\x17)\\n?/m';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			$this->parser->addIgnoreTag($m[0][1], strlen($m[0][0]));

			// Only add the reference if it does not already exist
			$id = strtolower($m[1][0]);
			if (!isset($this->text->linkReferences[$id]))
			{
				$this->text->hasReferences       = true;
				$this->text->linkReferences[$id] = $m[2][0];
			}
		}
	}
}