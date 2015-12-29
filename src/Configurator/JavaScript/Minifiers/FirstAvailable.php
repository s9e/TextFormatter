<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;

use ArrayAccess;
use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\MinifierList;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;

class FirstAvailable extends Minifier implements ArrayAccess
{
	use CollectionProxy;

	/**
	* @var MinifierList
	*/
	protected $collection;

	/**
	* Constructor
	*
	* @return void
	*/
	public function __construct()
	{
		$this->collection = new MinifierList;
		foreach (func_get_args() as $minifier)
		{
			$this->collection->add($minifier);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function minify($src)
	{
		foreach ($this->collection as $minifier)
		{
			try
			{
				return $minifier->minify($src);
			}
			catch (Exception $e)
			{
				// Do nothing
			}
		}

		throw new RuntimeException('No minifier available');
	}
}