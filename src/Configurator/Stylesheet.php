<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateChecker;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
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
	* @var array Array of wildcard templates, using prefix as key
	*/
	protected $wildcards = array();

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
		$prefixes  = array();
		$templates = array(
			'br' => '<br/>',
			'st' => '',
			'et' => '',
			'i'  => ''
		);

		// Iterate over the wildcards to collect their templates and their prefix
		foreach ($this->wildcards as $prefix => $template)
		{
			$inUse = false;
			$checkUnsafe = !($template instanceof UnsafeTemplate);

			// First, normalize the template without asserting its safety
			$template = TemplateHelper::normalizeUnsafe((string) $template);

			// Then, iterate over tags to assess the template's safety
			foreach ($this->tags as $tagName => $tag)
			{
				if (strncmp($tagName, $prefix . ':', strlen($prefix) + 1))
				{
					continue;
				}

				if ($checkUnsafe)
				{
					TemplateChecker::checkUnsafe($template, $tag);
				}

				$inUse = true;
			}

			if ($inUse)
			{
				$prefixes[$prefix] = 1;
				$templates[$prefix . ':*'] = $template;
			}
		}

		// Iterate over the tags to collect their templates and their prefix
		foreach ($this->tags as $tagName => $tag)
		{
			foreach ($tag->templates as $predicate => $template)
			{
				$match = $tagName;
				if ($predicate !== '')
				{
					$match .= '[' . $predicate . ']';
				}

				$pos = strpos($tagName, ':');
				if ($pos !== false)
				{
					$prefixes[substr($tagName, 0, $pos)] = 1;
				}

				$templates[$match] = ($template instanceof UnsafeTemplate)
				                   ? TemplateHelper::normalizeUnsafe((string) $template, $tag)
				                   : TemplateHelper::normalize((string) $template, $tag);
			}
		}

		// Declare all the namespaces in use at the top
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		// Append the namespace declarations to the stylesheet
		ksort($prefixes);
		foreach (array_keys($prefixes) as $prefix)
		{
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		// Start the stylesheet with the boilerplate stuff
		$xsl .= '><xsl:output method="' . $this->outputMethod . '" encoding="utf-8" indent="no"/>';

		// Add our templates
		/** @todo dedupe */
		foreach ($templates as $match => $template)
		{
			$xsl .= '<xsl:template match="' . htmlspecialchars($match) . '">'
			      . $template
			      . '</xsl:template>';
		}

		$xsl .= '</xsl:stylesheet>';

		return $xsl;
	}

	/**
	* Set a wildcard template for given namespace
	*
	* @param  string                     $prefix   Prefix of the namespace this template applies to
	* @param  string|TemplatePlaceholder $template Template's content
	* @return void
	*/
	public function setWildcardTemplate($prefix, $template)
	{
		// Use the tag name validator to validate the prefix
		if (!TagName::isValid($prefix . ':X'))
		{
			throw new InvalidArgumentException("Invalid prefix '" . $prefix . "'");
		}

		/**
		* @todo ensure that $template is the right type or something
		*/

		$this->wildcards[$prefix] = $template;
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