<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons;

use ArrayAccess;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\Emoticons\Configurator\EmoticonCollection;

class Configurator extends ConfiguratorBase implements ArrayAccess, Countable, Iterator
{
	use CollectionProxy;

	/**
	* @var string Regular expression to match after the emoticon
	*/
	public $afterMatch = '';

	/**
	* @var string Regular expression to match before the emoticon
	*/
	public $beforeMatch = '';

	/**
	* @var EmoticonCollection
	*/
	protected $collection;

	/**
	* @var Tag Tag used by this plugin
	*/
	protected $tag;

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

		$this->tag = ($this->configurator->tags->exists($this->tagName))
		           ? $this->configurator->tags->get($this->tagName)
		           : $this->configurator->tags->add($this->tagName);

		$this->tag->defaultTemplate = [$this, 'getTemplate'];
	}

	/**
	* @return array
	*/
	public function asConfig()
	{
		if (!count($this->collection))
		{
			return false;
		}

		// Grab the emoticons from the collection
		$codes = array_keys(iterator_to_array($this->collection));

		// Build the regexp used to match emoticons
		$regexp = '/'
		        . $this->beforeMatch
		        . RegexpBuilder::fromList($codes)
		        . $this->afterMatch
		        . '/S';

		$config = [
			'quickMatch' => $this->quickMatch,
			'regexp'     => $regexp,
			'tagName'    => $this->tagName
		];

		// Try to find a quickMatch if none is set
		if ($this->quickMatch === false)
		{
			$config['quickMatch'] = ConfigHelper::generateQuickMatchFromList($codes);
		}

		return $config;
	}

	/**
	* Generate the dynamic template that renders all emoticons
	*
	* @return string
	*/
	public function getTemplate()
	{
		$templates = [];
		foreach ($this->collection as $code => $template)
		{
			$templates[$template][] = $code;
		}

		$xsl = '';

		// Iterate over codes, replace codes with their representation as a string (with quotes)
		// and create variables as needed
		foreach ($templates as $template => &$codes)
		{
			foreach ($codes as &$code)
			{
				if (strpos($code, "'") === false)
				{
					// :)  produces <xsl:when test=".=':)'">
					$code = "'" . htmlspecialchars($code) . "'";
				}
				elseif (strpos($code, '"') === false)
				{
					// :') produces <xsl:when test=".=&quot;:')&quot;">
					$code = '&quot;' . $code . '&quot;';
				}
				else
				{
					// This code contains both ' and ". XPath 1.0 doesn't have a mechanism to escape
					// quotes, so we have to get creative and use concat() to join single-quote
					// chunks and double-quote chunks
					$toks = [];
					$pos  = 0;
					$len  = strlen($code);
					$c    = '"';
					while ($pos < $len)
					{
						$spn = strcspn($code, $c, $pos);
						if ($spn)
						{
							$toks[] = $c . substr($code, $pos, $spn) . $c;
							$pos += $spn;
						}
						$c = ($c === '"') ? "'" : '"';
					}

					$code = 'concat(' . htmlspecialchars(implode(',', $toks)) . ')';
				}
			}
			unset($code);
		}
		unset($codes);

		// Now build the <xsl:choose> node
		$xsl .= '<xsl:choose>';

		// Iterate over codes, create an <xsl:when> for each group of codes
		foreach ($templates as $template => $codes)
		{
			$xsl .= '<xsl:when test=".=' . implode(' or .=', $codes) . '">'
			      . $template
			      . '</xsl:when>';
		}

		// Finish it with an <xsl:otherwise> that displays the unknown codes as text
		$xsl .= '<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise>';

		// Now close everything and return
		$xsl .= '</xsl:choose>';

		return $xsl;
	}
}