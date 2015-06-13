<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Renderers;
use XSLTCache;
class XSLCache extends XSLT
{
	protected $filepath;
	protected $proc;
	public function __construct($filepath)
	{
		$this->filepath = $filepath;
		parent::__construct(\file_get_contents($this->filepath));
	}
	public function getFilepath()
	{
		return $this->filepath;
	}
	protected function load()
	{
		if (!isset($this->proc))
		{
			$this->proc = new XSLTCache;
			$this->proc->importStylesheet($this->filepath);
		}
	}
}