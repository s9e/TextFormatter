<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons;

use ArrayAccess;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Configurator\Traits\CollectionProxy;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase implements ArrayAccess
{
	use CollectionProxy;

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
	public function setUp()
	{
		$this->collection = new EmoticonCollection;

		$this->tag = (isset($this->configurator->tags[$this->tagName]))
		           ? $this->configurator->tags->get($this->tagName)
		           : $this->configurator->tags->add($this->tagName);
	}

	/**
	* @return array
	*/
	public function toConfig()
	{
		if (!count($this->collection))
		{
			return false;
		}

		// Grab the emoticons from the collection
		$codes = array_keys(iterator_to_array($this->collection));

		// Non-anchored pattern, will benefit from the S modifier
		$regexp = '/' . RegexpBuilder::fromList($codes) . '/S';

		return array(
			'regexp'  => $regexp,
			'tagName' => $this->tagName
		);
	}

	/**
	* Generate the dynamic template that renders all emoticons
	*
	* @return string
	*/
	protected function getXSL()
	{
		$templates = array();
		foreach ($this->emoticons as $code => $template)
		{
			$templates[$template][] = $code;
		}

		$xsl = '<xsl:template match="' . $this->tagName . '">';

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
					// This code contains both ' and " so we store its content in a variable
					$id = uniqid();

					$xsl .= '<xsl:variable name="e' . $id . '">'
					      . htmlspecialchars($code)
					      . '</xsl:variable>';

					$code = '$e' . $id;
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

		return $xsl;
	}
}