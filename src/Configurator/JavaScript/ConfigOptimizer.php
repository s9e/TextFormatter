<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

/**
* This class creates local variables to deduplicate complex objects
*/
class ConfigOptimizer
{
	/**
	* @var Encoder
	*/
	protected $encoder;

	/**
	* @var integer Minimum size of the JavaScript literal to be deduplicated
	*/
	protected $minSize = 8;

	/**
	* @var array Associative array containing the JavaScript representation of deduplicated
	*            data structures
	*/
	protected $objects;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->encoder = new Encoder;
		$this->reset();
	}

	/**
	* Return the var declarations for all deduplicated objects
	*
	* @return string JavaScript code
	*/
	public function getObjects()
	{
		$src = '';
		foreach ($this->objects as $varName => $js)
		{
			$src .= '/** @const */ var ' . $varName . '=' . $js . ";\n";
		}

		return $src;
	}

	/**
	* Optimize given object
	*
	* @param  array|Dictionary      $object Original object
	* @return array|Code|Dictionary         Original object or a JavaScript variable
	*/
	public function optimizeObject($object)
	{
		return $this->deduplicateObject($this->optimizeObjectContent($object));
	}

	/**
	* Optimize given object's content
	*
	* @param  array|Dictionary $object Original object
	* @return array|Dictionary         Modified object
	*/
	public function optimizeObjectContent($object)
	{
		foreach ($object as $k => $v)
		{
			if (is_array($v) || $v instanceof Dictionary)
			{
				$object[$k] = $this->optimizeObject($v);
			}
		}

		return $object;
	}

	/**
	* Clear the deduplicated objects stored in this instance
	*
	* @return void
	*/
	public function reset()
	{
		$this->objects = [];
	}

	/**
	* Deduplicate given object
	*
	* The object will be encoded into JavaScript. If the size of its representation exceeds
	* $this->minSize it will be stored in a variable and the name of the variable will be returned.
	* Otherwise, the source for the object is returned
	*
	* @param  array|Dictionary $object Original object
	* @return Code                     Object's source or variable holding the object's value
	*/
	protected function deduplicateObject($object)
	{
		$js = $this->encoder->encode($object);
		$k  = sprintf('o%08X', crc32($js));

		if (strlen($js) >= $this->minSize)
		{
			$this->objects[$k] = $js;
			$js = $k;
		}

		return new Code($js);
	}
}