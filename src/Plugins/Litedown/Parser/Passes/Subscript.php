<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

class Subscript extends AbstractScript
{
	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		$this->parseAbstractScript('SUB', '~', '/~[^\\x17\\s!"#$%&\'()*+,\\-.\\/:;<=>?@[\\]^_`{}|~]++~?/', '/~\\([^\\x17()]++\\)/');
	}
}