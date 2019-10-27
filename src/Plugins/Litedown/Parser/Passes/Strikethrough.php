<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;
class Strikethrough extends AbstractInlineMarkup
{
	public function parse()
	{
		$this->parseInlineMarkup('~~', '/~~[^\\x17]+?~~(?!~)/', 'DEL');
	}
}