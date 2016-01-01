<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;

use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;

class Repository
{
	/**
	* @var BBCodeMonkey Instance of BBCodeMonkey used to parse definitions
	*/
	protected $bbcodeMonkey;

	/**
	* @var DOMDocument Repository document
	*/
	protected $dom;

	/**
	* Constructor
	*
	* @param  mixed        $value        Either a DOMDocument or the path to a repository's XML file
	* @param  BBCodeMonkey $bbcodeMonkey Instance of BBCodeMonkey used to parse definitions
	* @return void
	*/
	public function __construct($value, BBCodeMonkey $bbcodeMonkey)
	{
		if (!($value instanceof DOMDocument))
		{
			if (!file_exists($value))
			{
				throw new InvalidArgumentException('Not a DOMDocument or the path to a repository file');
			}

			$dom = new DOMDocument;
			$dom->preserveWhiteSpace = false;

			$useErrors = libxml_use_internal_errors(true);
			$success = $dom->load($value);
			libxml_use_internal_errors($useErrors);

			if (!$success)
			{
				throw new InvalidArgumentException('Invalid repository file');
			}

			$value = $dom;
		}

		$this->bbcodeMonkey = $bbcodeMonkey;
		$this->dom = $value;
	}

	/**
	* Get a BBCode and its associated tag from this repository
	*
	* @param  string $name Name of the entry in the repository
	* @param  array  $vars Replacement variables
	* @return array        Array with three elements: "bbcode", "name" and "tag"
	*/
	public function get($name, array $vars = [])
	{
		// Everything before # should be a BBCode name
		$name = preg_replace_callback(
			'/^[^#]+/',
			function ($m)
			{
				return BBCode::normalizeName($m[0]);
			},
			$name
		);

		$xpath = new DOMXPath($this->dom);
		$node  = $xpath->query('//bbcode[@name="' . htmlspecialchars($name) . '"]')->item(0);

		if (!($node instanceof DOMElement))
		{
			throw new RuntimeException("Could not find '" . $name . "' in repository");
		}

		// Clone the node so we don't end up modifying the node in the repository
		$clonedNode = $node->cloneNode(true);

		// Replace all the <var> descendants if applicable
		foreach ($xpath->query('.//var', $clonedNode) as $varNode)
		{
			$varName = $varNode->getAttribute('name');

			if (isset($vars[$varName]))
			{
				$varNode->parentNode->replaceChild(
					$this->dom->createTextNode($vars[$varName]),
					$varNode
				);
			}
		}

		// Now we can parse the BBCode usage and prepare the template.
		// Grab the content of the <usage> element then use BBCodeMonkey to parse it
		$usage      = $xpath->evaluate('string(usage)', $clonedNode);
		$template   = $xpath->evaluate('string(template)', $clonedNode);
		$config     = $this->bbcodeMonkey->create($usage, $template);
		$bbcode     = $config['bbcode'];
		$bbcodeName = $config['bbcodeName'];
		$tag        = $config['tag'];

		// Set the optional tag name
		if ($node->hasAttribute('tagName'))
		{
			$bbcode->tagName = $node->getAttribute('tagName');
		}

		// Set the rules
		foreach ($xpath->query('rules/*', $node) as $ruleNode)
		{
			$methodName = $ruleNode->nodeName;
			$args       = [];

			if ($ruleNode->textContent)
			{
				$args[] = $ruleNode->textContent;
			}

			call_user_func_array([$tag->rules, $methodName], $args);
		}

		// Set predefined attributes
		foreach ($node->getElementsByTagName('predefinedAttributes') as $predefinedAttributes)
		{
			foreach ($predefinedAttributes->attributes as $attribute)
			{
				$bbcode->predefinedAttributes->set($attribute->name, $attribute->value);
			}
		}

		return [
			'bbcode'     => $bbcode,
			'bbcodeName' => $bbcodeName,
			'tag'        => $tag
		];
	}
}