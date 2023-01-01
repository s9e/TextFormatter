<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use s9e\SweetDOM\Document;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

class FunctionCache
{
	protected array $cache = [];

	/**
	* @var array Map of JavaScript interfaces that may be useful to Google Closure Compiler
	*/
	public array $elementInterfaces = [
		'iframe' => 'HTMLIFrameElement',
		'script' => 'HTMLScriptElement'
	];

	/**
	* Add all live preview events from given XSL
	*/
	public function addFromXSL(string $xsl): void
	{
		$dom = new Document;
		$dom->loadXML($xsl);

		foreach ($dom->query('//@*[starts-with(name(), "data-s9e-livepreview-on")]') as $attribute)
		{
			$avt = AVTHelper::parse($attribute->textContent);
			if (count($avt) !== 1 || $avt[0][0] !== 'literal')
			{
				continue;
			}

			// Use the unescaped value from AVTHelper
			$js  = $avt[0][1];
			$key = (string) Hasher::quickHash($js);

			// Make sure the code ends with a semicolon or a brace
			if (!preg_match('([;}]\\s*$)', $js, $m))
			{
				$js .= ';';
			}

			$this->cache[$key]['code']       = $js;
			$this->cache[$key]['elements'][] = $attribute->parentNode->tagName;
		}
	}

	/**
	* @return string Current cache as a JSON object
	*/
	public function getJSON(): string
	{
		$cache = [];
		foreach ($this->cache as $key => $entry)
		{
			$types = [];
			foreach ($entry['elements'] as $nodeName)
			{
				// Use the element's JavaScript interface if known, otherwise just use Element
				$types[] = '!' . ($this->elementInterfaces[$nodeName] ?? 'Element');
			}
			$types = array_unique($types);
			sort($types);

			$cache[$key] = json_encode((string) $key) . ':/**@this {' . implode('|', $types) . '}*/function(){' . $entry['code'] . '}';
		}
		ksort($cache);

		return '{' . implode(',', $cache) . '}';
	}
}