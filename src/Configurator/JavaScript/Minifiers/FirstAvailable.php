<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
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
* @method mixed    add(mixed $value, null $void)  Add (append) a value to this list
* @method mixed    append(mixed $value)           Append a value to this list
* @method array    asConfig()
* @method void     clear()                        Empty this collection
* @method bool     contains(mixed $value)         Test whether a given value is present in this collection
* @method integer  count()
* @method mixed    current()
* @method void     delete(string $key)            Delete a value from this list and remove gaps in keys
* @method bool     exists(string $key)            Test whether an item of given key exists
* @method mixed    get(string $key)               Return a value from this collection
* @method mixed    indexOf(mixed $value)          Find the index of a given value
* @method mixed    insert(integer $offset, mixed $value) Insert a value at an arbitrary 0-based position
* @method integer|string key()
* @method mixed    next()
* @method integer  normalizeKey(mixed $key)       Ensure that the key is a valid offset
* @method Minifier normalizeValue(Minifier|string $minifier) Normalize the value to an object
* @method bool     offsetExists(string|integer $offset)
* @method mixed    offsetGet(string|integer $offset)
* @method void     offsetSet(mixed $offset, mixed $value) Custom offsetSet() implementation to allow assignment with a null offset to append to the
* @method void     offsetUnset(string|integer $offset)
* @method string   onDuplicate(string|null $action) Query and set the action to take when add() is called with a key that already exists
* @method mixed    prepend(mixed $value)          Prepend a value to this list
* @method integer  remove(mixed $value)           Remove all items matching given value
* @method void     rewind()
* @method mixed    set(string $key, mixed $value) Set and overwrite a value in this collection
* @method bool     valid()
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