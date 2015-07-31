<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

/**
* @method mixed   add(string $key, mixed $value)
* @method array   asConfig()
* @method void    clear()
* @method bool    contains(mixed $value)
* @method integer count()
* @method mixed   current()
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method integer|string key()
* @method mixed   next()
* @method string  normalizeKey(string $key)
* @method mixed   normalizeValue(mixed $value)
* @method bool    offsetExists(string|integer $offset)
* @method mixed   offsetGet(string|integer $offset)
* @method void    offsetSet(string|integer $offset, mixed $value)
* @method void    offsetUnset(string|integer $offset)
* @method string  onDuplicate(string|null $action)
* @method void    rewind()
* @method mixed   set(string $key, mixed $value)
* @method bool    valid()
*/
class Configurator extends ConfiguratorBase implements ArrayAccess, Countable, Iterator
{
	/**
	* Forward all unknown method calls to $this->collection
	*
	* @param  string $methodName
	* @param  array  $args
	* @return mixed
	*/
	public function __call($methodName, $args)
	{
		return call_user_func_array(array($this->collection, $methodName), $args);
	}

	//==========================================================================
	// ArrayAccess
	//==========================================================================

	/**
	* @param  string|integer $offset
	* @return bool
	*/
	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}

	/**
	* @param  string|integer $offset
	* @return mixed
	*/
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}

	/**
	* @param  string|integer $offset
	* @param  mixed          $value
	* @return void
	*/
	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}

	/**
	* @param  string|integer $offset
	* @return void
	*/
	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}

	//==========================================================================
	// Countable
	//==========================================================================

	/**
	* @return integer
	*/
	public function count()
	{
		return count($this->collection);
	}

	//==========================================================================
	// Iterator
	//==========================================================================

	/**
	* @return mixed
	*/
	public function current()
	{
		return $this->collection->current();
	}

	/**
	* @return string|integer
	*/
	public function key()
	{
		return $this->collection->key();
	}

	/**
	* @return mixed
	*/
	public function next()
	{
		return $this->collection->next();
	}

	/**
	* @return void
	*/
	public function rewind()
	{
		$this->collection->rewind();
	}

	/**
	* @return boolean
	*/
	public function valid()
	{
		return $this->collection->valid();
	}

	/**
	* @var array List of whitelisted words as [word => true]
	*/
	protected $allowed = array();

	/**
	* @var string Name of attribute used for the replacement
	*/
	protected $attrName = 'with';

	/**
	* @var NormalizedCollection List of [word => replacement]
	*/
	protected $collection;

	/**
	* @var string Default string used to replace censored words
	*/
	protected $defaultReplacement = '****';

	/**
	* @var array Options passed to the RegexpBuilder
	*/
	protected $regexpOptions = array(
		'caseInsensitive' => true,
		'specialChars'    => array(
			'*' => '[\\pL\\pN]*',
			'?' => '.',
			' ' => '\\s*'
		)
	);

	/**
	* @var string Name of the tag used to mark censored words
	*/
	protected $tagName = 'CENSOR';

	/**
	* Plugin's setup
	*
	* Will initialize its collection and create the plugin's tag if it does not exist
	*/
	protected function setUp()
	{
		$this->collection = new NormalizedCollection;
		$this->collection->onDuplicate('replace');

		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create a tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Create the attribute and make it optional
		$tag->attributes->add($this->attrName)->required = false;

		// Ensure that censored content can't ever be used by other tags
		$tag->rules->ignoreTags();

		// Create a template that renders censored words either as their custom replacement or as
		// the default replacement
		$tag->template =
			'<xsl:choose>
				<xsl:when test="@' . $this->attrName . '">
					<xsl:value-of select="@' . htmlspecialchars($this->attrName) . '"/>
				</xsl:when>
				<xsl:otherwise>' . htmlspecialchars($this->defaultReplacement) . '</xsl:otherwise>
			</xsl:choose>';
	}

	/**
	* Add a word to the list of uncensored words
	*
	* @param  string $word Word to exclude from the censored list
	* @return void
	*/
	public function allow($word)
	{
		$this->allowed[$word] = true;
	}

	/**
	* Return an instance of s9e\TextFormatter\Plugins\Censor\Helper
	*
	* @return Helper
	*/
	public function getHelper()
	{
		$config = $this->asConfig();
		if (isset($config))
		{
			ConfigHelper::filterVariants($config);
		}
		else
		{
			// Use a dummy config with a regexp that doesn't match anything
			$config = array(
				'attrName' => $this->attrName,
				'regexp'   => '/(?!)/',
				'tagName'  => $this->tagName
			);
		}

		return new Helper($config);
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$words = array_diff_key(iterator_to_array($this->collection), $this->allowed);

		if (empty($words))
		{
			return;
		}

		// Create the config
		$config = array(
			'attrName' => $this->attrName,
			'regexp'   => $this->getWordsRegexp(array_keys($words)),
			'tagName'  => $this->tagName
		);

		// Add custom replacements
		$replacementWords = array();
		foreach ($words as $word => $replacement)
		{
			if (isset($replacement) && $replacement !== $this->defaultReplacement)
			{
				$replacementWords[$replacement][] = $word;
			}
		}

		foreach ($replacementWords as $replacement => $words)
		{
			$regexp = '/^' . RegexpBuilder::fromList($words, $this->regexpOptions) . '$/Diu';

			// Create a regexp with a JavaScript variant for each group of words
			$variant = new Variant($regexp);

			$regexp = str_replace('[\\pL\\pN]', '[^\\s!-\\/:-?]', $regexp);
			$variant->set('JS', RegexpConvertor::toJS($regexp));

			$config['replacements'][] = array($variant, $replacement);
		}

		// Add the whitelist
		if (!empty($this->allowed))
		{
			$config['allowed'] = $this->getWordsRegexp(array_keys($this->allowed));
		}

		return $config;
	}

	/**
	* Generate a regexp that matches the given list of words
	*
	* @param  array   $words List of words
	* @return Variant        Regexp in a Variant container, with a JS variant
	*/
	protected function getWordsRegexp(array $words)
	{
		$regexp = RegexpBuilder::fromList($words, $this->regexpOptions);

		// Force atomic grouping for performance. Theorically it could prevent some matches but in
		// practice it shouldn't happen
		$regexp = preg_replace('/(?<!\\\\)((?>\\\\\\\\)*)\\(\\?:/', '$1(?>', $regexp);

		// Create a variant for the return value
		$variant = new Variant('/(?<![\\pL\\pN])' . $regexp . '(?![\\pL\\pN])/Siu');

		// JavaScript regexps don't support Unicode properties, so instead of Unicode letters
		// we'll accept any non-whitespace, non-common punctuation
		$regexp = str_replace('[\\pL\\pN]', '[^\\s!-\\/:-?]', $regexp);
		$variant->set('JS', new RegExp('(?:^|\\W)' . $regexp . '(?!\\w)', 'gi'));

		return $variant;
	}
}