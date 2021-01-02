<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Helpers\RegexpParser;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCode;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeCollection;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\Repository;
use s9e\TextFormatter\Plugins\BBCodes\Configurator\RepositoryCollection;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* @method mixed   add(string $key, mixed $value) Add an item to this collection
* @method array   asConfig()
* @method void    clear()                        Empty this collection
* @method bool    contains(mixed $value)         Test whether a given value is present in this collection
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)            Delete an item from this collection
* @method bool    exists(string $key)            Test whether an item of given key exists
* @method mixed   get(string $key)               Return a value from this collection
* @method mixed   indexOf(mixed $value)          Find the index of a given value
* @method integer|string key()
* @method mixed   next()
* @method string  normalizeKey(string $key)      Normalize an item's key
* @method mixed   normalizeValue(mixed $value)   Normalize a value for storage
* @method bool    offsetExists(string|integer $offset)
* @method mixed   offsetGet(string|integer $offset)
* @method void    offsetSet(string|integer $offset, mixed $value)
* @method void    offsetUnset(string|integer $offset)
* @method string  onDuplicate(string|null $action) Query and set the action to take when add() is called with a key that already exists
* @method void    rewind()
* @method mixed   set(string $key, mixed $value) Set and overwrite a value in this collection
* @method bool    valid()
*/
class Configurator extends ConfiguratorBase implements ArrayAccess, Countable, Iterator
{
	use CollectionProxy;

	/**
	* @var BBCodeMonkey Instance of BBCodeMonkey used to parse definitions
	*/
	public $bbcodeMonkey;

	/**
	* @var BBCodeCollection BBCode collection
	*/
	public $collection;

	/**
	* {@inheritdoc}
	*/
	protected $quickMatch = '[';

	/**
	* {@inheritdoc}
	*/
	protected $regexp = '#\\[/?(\\*|[-\\w]+)(?=[\\]\\s=:/])#';

	/**
	* @var RepositoryCollection BBCode repositories
	*/
	public $repositories;

	/**
	* Plugin setup
	*
	* @return void
	*/
	protected function setUp()
	{
		$this->bbcodeMonkey = new BBCodeMonkey($this->configurator);
		$this->collection   = new BBCodeCollection;
		$this->repositories = new RepositoryCollection($this->bbcodeMonkey);
		$this->repositories->add('default', __DIR__ . '/Configurator/repository.xml');
	}

	/**
	* Add a BBCode using their human-readable representation
	*
	* @see s9e\TextFormatter\Plugins\BBCodes\Configurator\BBCodeMonkey
	*
	* @param  string $usage    BBCode's usage
	* @param  string|\s9e\TextFormatter\Configurator\Items\Template $template BBCode's template
	* @param  array  $options  Supported: 'tagName' and 'rules'
	* @return BBCode           Newly-created BBCode
	*/
	public function addCustom($usage, $template, array $options = [])
	{
		$config = $this->bbcodeMonkey->create($usage, $template);

		if (isset($options['tagName']))
		{
			$config['bbcode']->tagName = $options['tagName'];
		}

		if (isset($options['rules']))
		{
			$config['tag']->rules->merge($options['rules']);
		}

		return $this->addFromConfig($config);
	}

	/**
	* Add a BBCode from a repository
	*
	* @param  string $name       Name of the entry in the repository
	* @param  mixed  $repository Name of the repository to use as source, or instance of Repository
	* @param  array  $vars       Variables that will replace default values in the tag definition
	* @return BBCode             Newly-created BBCode
	*/
	public function addFromRepository($name, $repository = 'default', array $vars = [])
	{
		// Load the Repository if necessary
		if (!($repository instanceof Repository))
		{
			if (!$this->repositories->exists($repository))
			{
				throw new InvalidArgumentException("Repository '" . $repository . "' does not exist");
			}

			$repository = $this->repositories->get($repository);
		}

		return $this->addFromConfig($repository->get($name, $vars));
	}

	/**
	* Add a BBCode and its tag based on the return config from BBCodeMonkey
	*
	* @param  array  $config BBCodeMonkey::create()'s return array
	* @return BBCode
	*/
	protected function addFromConfig(array $config)
	{
		$bbcodeName = $config['bbcodeName'];
		$bbcode     = $config['bbcode'];
		$tag        = $config['tag'];

		// If the BBCode doesn't specify a tag name, it's the same as the BBCode
		if (!isset($bbcode->tagName))
		{
			$bbcode->tagName = $bbcodeName;
		}

		// Normalize this tag's templates
		$this->configurator->templateNormalizer->normalizeTag($tag);

		// Test whether this BBCode/tag is safe before adding it
		$this->configurator->templateChecker->checkTag($tag);

		// Add our BBCode then its tag
		$this->collection->add($bbcodeName, $bbcode);
		$this->configurator->tags->add($bbcode->tagName, $tag);

		return $bbcode;
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!count($this->collection))
		{
			return;
		}

		return [
			'bbcodes'    => $this->collection->asConfig(),
			'quickMatch' => $this->quickMatch,
			'regexp'     => $this->regexp
		];
	}
}