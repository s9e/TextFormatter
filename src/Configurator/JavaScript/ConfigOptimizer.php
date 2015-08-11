<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
class ConfigOptimizer
{
	protected $encoder;
	protected $minSize = 8;
	protected $objects;
	public function __construct()
	{
		$this->encoder = new Encoder;
		$this->reset();
	}
	public function getObjects()
	{
		$src = '';
		foreach ($this->objects as $varName => $js)
			$src .= '/** @const */ var ' . $varName . '=' . $js . ";\n";
		return $src;
	}
	public function optimizeObject($object)
	{
		return $this->deduplicateObject($this->optimizeObjectContent($object));
	}
	public function optimizeObjectContent($object)
	{
		foreach ($object as $k => $v)
			if (\is_array($v) || $v instanceof Dictionary)
				$object[$k] = $this->optimizeObject($v);
		return $object;
	}
	public function reset()
	{
		$this->objects = array();
	}
	protected function deduplicateObject($object)
	{
		$js = $this->encoder->encode($object);
		$k  = \sprintf('o%08X', \crc32($js));
		if (\strlen($js) >= $this->minSize)
		{
			$this->objects[$k] = $js;
			$js = $k;
		}
		return new Code($js);
	}
}