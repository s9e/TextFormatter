<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons;

use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Items\Regexp;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\Emoticons\Configurator\EmoticonCollection;
use s9e\TextFormatter\Utils\XPath;

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
* @method string  normalizeValue(string $value)  Normalize an emoticon's template
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
	* @var EmoticonCollection
	*/
	protected $collection;

	/**
	* @var string PCRE subpattern used in a negative lookbehind assertion before the emoticons
	*/
	public $notAfter = '';

	/**
	* @var string PCRE subpattern used in a negative lookahead assertion after the emoticons
	*/
	public $notBefore = '';

	/**
	* @var string XPath expression that, if true, forces emoticons to be rendered as text
	*/
	public $notIfCondition;

	/**
	* {@inheritdoc}
	*/
	protected $onDuplicateAction = 'replace';

	/**
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'E';

	/**
	* Plugin's setup
	*
	* Will create the tag used by this plugin
	*/
	protected function setUp()
	{
		$this->collection = new EmoticonCollection;

		if (!$this->configurator->tags->exists($this->tagName))
		{
			$this->configurator->tags->add($this->tagName);
		}
	}

	/**
	* Create the template used for emoticons
	*
	* @return void
	*/
	public function finalize()
	{
		$tag = $this->getTag();

		if (!isset($tag->template))
		{
			$tag->template = $this->getTemplate();
		}
	}

	/**
	* @return array
	*/
	public function asConfig()
	{
		if (!count($this->collection))
		{
			return;
		}

		// Grab the emoticons from the collection
		$codes = array_keys(iterator_to_array($this->collection));

		// Build the regexp used to match emoticons
		$regexp = '/';

		if ($this->notAfter !== '')
		{
			$regexp .= '(?<!' . $this->notAfter . ')';
		}

		$regexp .= RegexpBuilder::fromList($codes);

		if ($this->notBefore !== '')
		{
			$regexp .= '(?!' . $this->notBefore . ')';
		}

		$regexp .= '/S';

		// Set the Unicode mode if Unicode properties are used
		if (preg_match('/\\\\[pP](?>\\{\\^?\\w+\\}|\\w\\w?)/', $regexp))
		{
			$regexp .= 'u';
		}

		// Force the regexp to use atomic grouping for performance
		$regexp = preg_replace('/(?<!\\\\)((?>\\\\\\\\)*)\\(\\?:/', '$1(?>', $regexp);

		// Prepare the config array
		$config = [
			'quickMatch' => $this->quickMatch,
			'regexp'     => $regexp,
			'tagName'    => $this->tagName
		];

		// If notAfter is used, we need to create a JavaScript-specific regexp that does not use a
		// lookbehind assertion, and we add the notAfter subpattern to the config as a variant
		if ($this->notAfter !== '')
		{
			// Skip the first assertion by skipping the first N characters, where N equals the
			// length of $this->notAfter plus 1 for the first "/" and 5 for "(?<!)"
			$lpos = 6 + strlen($this->notAfter);
			$rpos = strrpos($regexp, '/');
			$jsRegexp = RegexpConvertor::toJS('/' . substr($regexp, $lpos, $rpos - $lpos) . '/', true);

			$config['regexp'] = new Regexp($regexp);
			$config['regexp']->setJS($jsRegexp);

			$config['notAfter'] = new Regexp('/' . $this->notAfter . '/');
		}

		// Try to find a quickMatch if none is set
		if ($this->quickMatch === false)
		{
			$config['quickMatch'] = ConfigHelper::generateQuickMatchFromList($codes);
		}

		return $config;
	}

	/**
	* {@inheritdoc}
	*/
	public function getJSHints()
	{
		return ['EMOTICONS_NOT_AFTER' => (int) !empty($this->notAfter)];
	}

	/**
	* Generate the dynamic template that renders all emoticons
	*
	* @return string
	*/
	public function getTemplate()
	{
		// Build the <xsl:choose> node
		$xsl = '<xsl:choose>';

		// First, test whether the emoticon should be rendered as text if applicable
		if (!empty($this->notIfCondition))
		{
			$xsl .= '<xsl:when test="' . htmlspecialchars($this->notIfCondition, ENT_COMPAT) . '">'
			      . '<xsl:value-of select="."/>'
			      . '</xsl:when>'
			      . '<xsl:otherwise>'
			      . '<xsl:choose>';
		}

		// Iterate over codes, create an <xsl:when> for each emote
		foreach ($this->collection as $code => $template)
		{
			$xsl .= '<xsl:when test=".=' . htmlspecialchars(XPath::export($code), ENT_COMPAT) . '">'
			      . $template
			      . '</xsl:when>';
		}

		// Finish it with an <xsl:otherwise> that displays the unknown codes as text
		$xsl .= '<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise>';

		// Close the emote switch
		$xsl .= '</xsl:choose>';

		// Close the "notIf" condition if applicable
		if (!empty($this->notIfCondition))
		{
			$xsl .= '</xsl:otherwise></xsl:choose>';
		}

		return $xsl;
	}
}