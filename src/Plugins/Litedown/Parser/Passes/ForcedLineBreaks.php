<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

class ForcedLineBreaks extends AbstractPass
{
	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		$pos = $this->text->indexOf("  \n");
		while ($pos !== false)
		{
			$this->parser->addBrTag($pos + 2)->cascadeInvalidationTo(
				$this->parser->addVerbatim($pos + 2, 1)
			);
			$pos = $this->text->indexOf("  \n", $pos + 3);
		}
	}
}