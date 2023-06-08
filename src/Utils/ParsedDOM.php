<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils;

use const LIBXML_NONET;
use s9e\TextFormatter\Utils\ParsedDOM\Document;

abstract class ParsedDOM
{
	public static function loadXML(string $xml): Document
	{
		$dom = new Document;
		$dom->loadXML($xml, LIBXML_NONET);

		return $dom;
	}
}