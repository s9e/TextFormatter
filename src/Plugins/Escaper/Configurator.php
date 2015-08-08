<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Escaper;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
class Configurator extends ConfiguratorBase
{
	protected $quickMatch = '\\';
	protected $regexp;
	public function escapeAll($bool = \true)
	{
		$this->regexp = ($bool) ? '/\\\\./su' : '/\\\\[-!#()*+.:<>@[\\\\\\]^_`{}]/';
	}
	protected function setUp()
	{
		$this->escapeAll(\false);
	}
}