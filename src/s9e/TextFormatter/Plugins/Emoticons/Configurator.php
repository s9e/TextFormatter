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
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Plugins\Emoticons\Configurator\EmoticonCollection;

class Configurator extends ConfiguratorBase implements ArrayAccess, Countable, Iterator
{
	use CollectionProxy;

	/**
	* @var string Head of the regular expression to match emoticons
	*/
	public $regexpStart = '/';

	/**
	* @var string Tail of the regular expression to match emoticons
	*/
	public $regexpEnd = '/S';

	/**
	* @var EmoticonCollection
	*/
	protected $collection;

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
		$this->configurator->tags[$this->tagName]->defaultTemplate = $this->getTemplate();
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
		$regexp = $this->regexpStart
		        . RegexpBuilder::fromList($codes, ['delimiter' => $this->regexpStart[0]])
		        . $this->regexpEnd;

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
		// Group the codes by template in order to merge duplicate templates. Replace codes with
		// their representation as a string (with quotes)
		$templates = [];
		foreach ($this->collection as $code => $template)
		{
			$templates[$template][] = htmlspecialchars(TemplateHelper::asXPath($code));
		}

		// Build the <xsl:choose> node
		$xsl = '<xsl:choose>';

		// Iterate over codes, create an <xsl:when> for each group of codes
		foreach ($templates as $template => $codes)
		{
			$xsl .= '<xsl:when test=".=' . implode('or.=', $codes) . '">'
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