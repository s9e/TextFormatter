<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Censor;

use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
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
	* @var array List of whitelisted words as [word => true]
	*/
	protected $allowed = [];

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
	protected $regexpOptions = [
		'caseInsensitive' => true,
		'specialChars'    => [
			'*' => '[\\pL\\pN]*',
			'?' => '.',
			' ' => '\\s*'
		]
	];

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
				<xsl:otherwise>' . htmlspecialchars($this->defaultReplacement, ENT_COMPAT) . '</xsl:otherwise>
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
			$config = ConfigHelper::filterConfig($config, 'PHP');
		}
		else
		{
			// Use a dummy config with a regexp that doesn't match anything
			$config = [
				'attrName' => $this->attrName,
				'regexp'   => '/(?!)/',
				'tagName'  => $this->tagName
			];
		}

		return new Helper($config);
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$words = $this->getWords();

		if (empty($words))
		{
			return;
		}

		// Create the config
		$config = [
			'attrName'   => $this->attrName,
			'regexp'     => $this->getWordsRegexp(array_keys($words)),
			'regexpHtml' => $this->getWordsRegexp(array_map('htmlspecialchars', array_keys($words))),
			'tagName'    => $this->tagName
		];

		// Add custom replacements
		$replacementWords = [];
		foreach ($words as $word => $replacement)
		{
			if (isset($replacement) && $replacement !== $this->defaultReplacement)
			{
				$replacementWords[$replacement][] = $word;
			}
		}

		foreach ($replacementWords as $replacement => $words)
		{
			$wordsRegexp = '/^' . RegexpBuilder::fromList($words, $this->regexpOptions) . '$/Diu';

			$regexp = new Regexp($wordsRegexp);
			$regexp->setJS(RegexpConvertor::toJS(str_replace('[\\pL\\pN]', '[^\\s!-\\/:-?]', $wordsRegexp)));

			$config['replacements'][] = [$regexp, $replacement];
		}

		// Add the whitelist
		if (!empty($this->allowed))
		{
			$config['allowed'] = $this->getWordsRegexp(array_keys($this->allowed));
		}

		return $config;
	}

	/**
	* {@inheritdoc}
	*/
	public function getJSHints()
	{
		$hints = [
			'CENSOR_HAS_ALLOWED'      => !empty($this->allowed),
			'CENSOR_HAS_REPLACEMENTS' => false
		];
		foreach ($this->getWords() as $replacement)
		{
			if (isset($replacement) && $replacement !== $this->defaultReplacement)
			{
				$hints['CENSOR_HAS_REPLACEMENTS'] = true;
				break;
			}
		}

		return $hints;
	}

	/**
	* Return a list of censored words
	*
	* @return string[]
	*/
	protected function getWords()
	{
		return array_diff_key(iterator_to_array($this->collection), $this->allowed);
	}

	/**
	* Generate a regexp that matches the given list of words
	*
	* @param  array   $words List of words
	* @return Regexp         Regexp instance with a Unicode-free JS variant
	*/
	protected function getWordsRegexp(array $words)
	{
		$expr  = RegexpBuilder::fromList($words, $this->regexpOptions);
		$regexp = new Regexp('/(?<![\\pL\\pN])' . $expr . '(?![\\pL\\pN])/Siu');

		// JavaScript regexps don't support Unicode properties, so instead of Unicode letters
		// we'll accept any non-whitespace, non-common punctuation
		$expr = str_replace('[\\pL\\pN]', '[^\\s!-\\/:-?]', $expr);
		$expr = str_replace('(?>',        '(?:',            $expr);
		$regexp->setJS('/(?:^|\\W)' . $expr . '(?!\\w)/gi');

		return $regexp;
	}
}