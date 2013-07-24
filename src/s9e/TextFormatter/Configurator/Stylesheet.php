<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\StylesheetParameterCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Template;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Configurator\Traits\Configurable;
use s9e\TextFormatter\Configurator\Validators\TagName;

class Stylesheet
{
	use Configurable;

	/**
	* @var Configurator Instance of Configurator this stylesheet is attached to
	*/
	protected $configurator;

	/**
	* @var string Output method
	*/
	protected $outputMethod = 'html';

	/**
	* @var StylesheetParameterCollection
	*/
	protected $parameters;

	/**
	* @var array Array of wildcard templates, using prefix as key
	*/
	protected $wildcards = [];

	/**
	* Constructor
	*
	* @param  Configurator $configurator Instance of Configurator this stylesheet is attached to
	* @return void
	*/
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
		$this->parameters   = new StylesheetParameterCollection;
	}

	/**
	* Get the finalized XSL stylesheet
	*
	* @return string
	*/
	public function get()
	{
		// Give plugins the opportunity to finalize their templates
		$this->configurator->plugins->finalize();

		$prefixes  = [];
		$templates = [
			'p'  => '<p><xsl:apply-templates/></p>',
			'br' => '<br/>',
			'st' => '',
			'et' => '',
			'i'  => ''
		];

		// Iterate over the wildcards to collect their templates and their prefix
		foreach ($this->wildcards as $prefix => $template)
		{
			// Check this template if it's not an UnsafeTemplate
			$checkTemplate = !($template instanceof UnsafeTemplate);

			// Normalize this template if applicable
			if (!$template->isNormalized())
			{
				$template->normalize($this->configurator->templateNormalizer);
			}

			// Cast this template as string so it can be checked and stored
			$template = (string) $template;

			// Then, iterate over tags to assess the template's safeness
			if ($checkTemplate)
			{
				foreach ($this->configurator->tags as $tagName => $tag)
				{
					// Ensure that the tag is in the right namespace
					if (strncmp($tagName, $prefix . ':', strlen($prefix) + 1))
					{
						continue;
					}

					// Only check for safeness if the tag has no default template set
					if (!$tag->templates->exists(''))
					{
						$this->configurator->templateChecker->checkTemplate($template, $tag);
					}
				}
			}

			// Record the prefix and template
			$prefixes[$prefix] = 1;
			$templates[$prefix . ':*'] = $template;
		}

		// Iterate over the tags to collect their templates and their prefix
		foreach ($this->configurator->tags as $tagName => $tag)
		{
			// Normalize this tag's templates
			$this->configurator->templateNormalizer->normalizeTag($tag);

			// Check the safeness of this tag's templates
			$this->configurator->templateChecker->checkTag($tag);

			foreach ($tag->templates as $predicate => $template)
			{
				// Build the match rule used by this template
				$match = $tagName;
				if ($predicate !== '')
				{
					// Append this template's predicate
					$match .= '[' . $predicate . ']';
				}

				// Record the tag's prefix
				$pos = strpos($tagName, ':');
				if ($pos !== false)
				{
					$prefixes[substr($tagName, 0, $pos)] = 1;
				}

				// Record the template as a string
				$templates[$match] = (string) $template;
			}
		}

		// Declare all the namespaces in use at the top
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		// Append the namespace declarations to the stylesheet
		$prefixes = array_keys($prefixes);
		sort($prefixes);
		foreach ($prefixes as $prefix)
		{
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		/**
		* Exclude those prefixes to keep the HTML neat
		*
		* @link http://lenzconsulting.com/namespaces-in-xslt/#exclude-result-prefixes
		*/
		if ($prefixes)
		{
			$xsl .= ' exclude-result-prefixes="' . implode(' ', $prefixes) . '"';
		}

		// Start the stylesheet with the boilerplate stuff
		$xsl .= '><xsl:output method="' . $this->outputMethod . '" encoding="utf-8" indent="no"';
		if ($this->outputMethod === 'xml')
		{
			$xsl .= ' omit-xml-declaration="yes"';
		}
		$xsl .= '/>';

		// Add stylesheet parameters
		foreach ($this->getUsedParameters() as $paramName => $expr)
		{
			$xsl .= '<xsl:param name="' . htmlspecialchars($paramName) . '"';

			// Add the default value if the parameter has one
			if (isset($expr) && $expr !== "''" && $expr !== '""')
			{
				$xsl .= ' select="' . htmlspecialchars($expr) . '"';
			}

			$xsl .= '/>';
		}

		// Group templates by content so we can deduplicate them
		$groupedTemplates = [];
		foreach ($templates as $match => $template)
		{
			$groupedTemplates[$template][] = $match;
		}

		foreach ($groupedTemplates as $template => $matches)
		{
			// Sort the matches, join them and don't forget to escape special chars
			sort($matches);
			$match = htmlspecialchars(implode('|', $matches));

			// Open the template element
			$xsl .= '<xsl:template match="' . $match . '"';

			// Make it a self-closing element if the template is empty
			if ($template === '')
			{
				$xsl .= '/>';
			}
			else
			{
				$xsl .= '>' . $template . '</xsl:template>';
			}
		}

		$xsl .= '</xsl:stylesheet>';

		return $xsl;
	}

	/**
	* Get all the parameters and values used in this stylesheet
	*
	* @return array Associative array of [paramName => xpathExpression]
	*/
	public function getUsedParameters()
	{
		$params = [];

		// Collect all the parameters used by tags' templates and assign them an empty string
		foreach ($this->configurator->tags as $tag)
		{
			foreach ($tag->templates as $template)
			{
				foreach ($template->getParameters() as $paramName)
				{
					$params[$paramName] = "''";
				}
			}
		}

		// Collect all the parameters used in wildcards and assign them an empty string
		foreach ($this->wildcards as $xsl)
		{
			foreach (TemplateHelper::getParametersFromXSL($xsl) as $paramName)
			{
				$params[$paramName] = "''";
			}
		}

		// Add parameters that have been formally defined
		foreach ($this->parameters as $paramName => $parameter)
		{
			$params[$paramName] = (string) $parameter;
		}

		// Keep them neat and ordered
		ksort($params);

		return $params;
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

		if (!($template instanceof Template))
		{
			$template = new Template($template);
		}

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