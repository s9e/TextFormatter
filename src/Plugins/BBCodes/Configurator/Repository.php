<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\BBCodes\Configurator;
use DOMDocument;
use DOMElement;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Items\Tag;
class Repository
{
	protected $bbcodeMonkey;
	protected $dom;
	protected $xpath;
	public function __construct($value, BBCodeMonkey $bbcodeMonkey)
	{
		$this->bbcodeMonkey = $bbcodeMonkey;
		$this->dom          = ($value instanceof DOMDocument) ? $value : $this->loadRepository($value);
		$this->xpath        = new DOMXPath($this->dom);
	}
	public function get($name, array $vars = [])
	{
		$name = BBCode::normalizeName($name);
		$node = $this->xpath->query('//bbcode[@name="' . $name . '"]')->item(0);
		if (!($node instanceof DOMElement))
			throw new RuntimeException("Could not find '" . $name . "' in repository");
		$node = $node->cloneNode(\true);
		$this->replaceVars($node, $vars);
		$usage    = $this->xpath->evaluate('string(usage)', $node);
		$template = $this->xpath->evaluate('string(template)', $node);
		$config   = $this->bbcodeMonkey->create($usage, $template);
		if ($node->hasAttribute('tagName'))
			$config['bbcode']->tagName = $node->getAttribute('tagName');
		$this->addRules($node, $config['tag']);
		return $config;
	}
	protected function addRules(DOMElement $node, Tag $tag)
	{
		foreach ($this->xpath->query('rules/*', $node) as $ruleNode)
		{
			$methodName = $ruleNode->nodeName;
			$args       = [];
			if ($ruleNode->textContent)
				$args[] = $ruleNode->textContent;
			\call_user_func_array([$tag->rules, $methodName], $args);
		}
	}
	protected function createRepositoryException($filepath)
	{
		return new InvalidArgumentException(\var_export($filepath, \true) . ' is not a valid BBCode repository file');
	}
	protected function loadRepository($filepath)
	{
		if (!\file_exists($filepath))
			throw $this->createRepositoryException($filepath);
		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = \false;
		if (!$dom->loadXML(\file_get_contents($filepath), \LIBXML_NOERROR))
			throw $this->createRepositoryException($filepath);
		return $dom;
	}
	protected function replaceVars(DOMElement $node, array $vars)
	{
		foreach ($this->xpath->query('.//var', $node) as $varNode)
		{
			$varName = $varNode->getAttribute('name');
			if (isset($vars[$varName]))
				$varNode->parentNode->replaceChild(
					$this->dom->createTextNode($vars[$varName]),
					$varNode
				);
		}
	}
}