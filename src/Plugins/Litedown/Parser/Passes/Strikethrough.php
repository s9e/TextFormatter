<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

class Strikethrough extends AbstractInlineMarkup
{
	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		$this->parseInlineMarkup('~~', '/~~[^\\x17]+?~~(?!~)/', 'DEL');
	}
}