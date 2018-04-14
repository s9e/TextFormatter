<?php

/*
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
	protected $bbcodeMonkey;
	protected $dom;
	public function __construct($value, BBCodeMonkey $bbcodeMonkey)
	{
		$this->bbcodeMonkey = $bbcodeMonkey;
		$this->dom          = ($value instanceof DOMDocument) ? $value : $this->loadRepository($value);
	}
	public function get($name, array $vars = [])
	{
		$name = \preg_replace_callback(
			'/^[^#]+/',
			function ($m)
			{
				return BBCode::normalizeName($m[0]);
			},
			$name
		);
		$xpath = new DOMXPath($this->dom);
		$node  = $xpath->query('//bbcode[@name="' . \htmlspecialchars($name) . '"]')->item(0);
		if (!($node instanceof DOMElement))
			throw new RuntimeException("Could not find '" . $name . "' in repository");
		$clonedNode = $node->cloneNode(\true);
		foreach ($xpath->query('.//var', $clonedNode) as $varNode)
		{
			$varName = $varNode->getAttribute('name');
			if (isset($vars[$varName]))
				$varNode->parentNode->replaceChild(
					$this->dom->createTextNode($vars[$varName]),
					$varNode
				);
		}
		$usage      = $xpath->evaluate('string(usage)', $clonedNode);
		$template   = $xpath->evaluate('string(template)', $clonedNode);
		$config     = $this->bbcodeMonkey->create($usage, $template);
		$bbcode     = $config['bbcode'];
		$bbcodeName = $config['bbcodeName'];
		$tag        = $config['tag'];
		if ($node->hasAttribute('tagName'))
			$bbcode->tagName = $node->getAttribute('tagName');
		foreach ($xpath->query('rules/*', $node) as $ruleNode)
		{
			$methodName = $ruleNode->nodeName;
			$args       = [];
			if ($ruleNode->textContent)
				$args[] = $ruleNode->textContent;
			\call_user_func_array([$tag->rules, $methodName], $args);
		}
		return [
			'bbcode'     => $bbcode,
			'bbcodeName' => $bbcodeName,
			'tag'        => $tag
		];
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
}