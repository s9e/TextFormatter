<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;

use ArrayAccess;
use Exception;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\MinifierList;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;


/**
* @method mixed   add(mixed $value, null $void)
* @method mixed   append(mixed $value)
* @method array   asConfig()
* @method void    clear()
* @method bool    contains(mixed $value)
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method mixed   insert(integer $offset, mixed $value)
* @method integer|string key()
* @method mixed   next()
* @method integer normalizeKey(mixed $key)
* @method Minifier normalizeValue(Minifier|string $minifier)
* @method bool    offsetExists(string|integer $offset)
* @method mixed   offsetGet(string|integer $offset)
* @method void    offsetSet(mixed $offset, mixed $value)
* @method void    offsetUnset(string|integer $offset)
* @method string  onDuplicate(string|null $action)
* @method mixed   prepend(mixed $value)
* @method integer remove(mixed $value)
* @method void    rewind()
* @method mixed   set(string $key, mixed $value)
* @method bool    valid()
*/
class FirstAvailable extends Minifier implements ArrayAccess
{
	use CollectionProxy;

	/**
	* @var MinifierList
	*/
	protected $collection;

	/**
	* Constructor
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