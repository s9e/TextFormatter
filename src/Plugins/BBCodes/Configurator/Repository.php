<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2018 The s9e Authors
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
	*/
	public function __construct($value, BBCodeMonkey $bbcodeMonkey)
	{
		$this->bbcodeMonkey = $bbcodeMonkey;
		$this->dom          = ($value instanceof DOMDocument) ? $value : $this->loadRepository($value);
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
		$name  = BBCode::normalizeName($name);
		$xpath = new DOMXPath($this->dom);
		$node  = $xpath->query('//bbcode[@name="' . $name . '"]')->item(0);

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

		return [
			'bbcode'     => $bbcode,
			'bbcodeName' => $bbcodeName,
			'tag'        => $tag
		];
	}

	/**
	* Create an exception for a bad repository file path
	*
	* @param  mixed $filepath
	* @return InvalidArgumentException
	*/
	protected function createRepositoryException($filepath)
	{
		return new InvalidArgumentException(var_export($filepath, true) . ' is not a valid BBCode repository file');
	}

	/**
	* Load a repository file into a DOMDocument
	*
	* @param  string $filepath
	* @return DOMDocument
	*/
	protected function loadRepository($filepath)
	{
		if (!file_exists($filepath))
		{
			throw $this->createRepositoryException($filepath);
		}

		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		if (!$dom->loadXML(file_get_contents($filepath), LIBXML_NOERROR))
		{
			throw $this->createRepositoryException($filepath);
		}

		return $dom;
	}
}