<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Validators\TagName;

class Stylesheet
{
	/**
	* @var string Output method
	*/
	protected $outputMethod = 'html';

	/**
	* @var TagCollection
	*/
	protected $tags;

	/**
	* Constructor
	*
	* @param  TagCollection $tags Tag collection from which templates are pulled
	* @return void
	*/
	public function __construct(TagCollection $tags)
	{
		$this->tags = $tags;
	}

	/**
	* 
	*
	* @return string
	*/
	public function get()
	{
		// Declare all the namespaces in use at the top
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		// Collect the unique prefixes used in tag names
		$prefixes = array();
		foreach ($this->tags as $tagName => $tag)
		{
			$pos = strpos($tagName, ':');

			if ($pos !== false)
			{
				$prefixes[substr($tagName, 0, $pos)] = 1;
			}
		}

		foreach (array_keys($prefixes) as $prefix)
		{
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		// Start the stylesheet with the boilerplate stuff
		$xsl .= '><xsl:output method="' . $this->outputMethod . '" encoding="utf-8" indent="no"/>';

		$xsl .= '</xsl:stylesheet>';

		return $xsl;
	}

	/**
	* Set the output method of this stylesheet
	*
	* @param  string $method Either "html" (default) or "xml"
	* @return void
	*/
	public function setOutputMethod($method)
	{
		if ($method !== 'html' && $method !== 'xml')
		{
			throw new InvalidArgumentException('Only html and xml methods are supported');
		}

		$this->outputMethod = $method;
	}
}