<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\AbstractStaticUrlReplacer;

use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

abstract class AbstractConfigurator extends ConfiguratorBase
{
	/**
	* @var string[] File extensions allowed in URLs
	*/
	public array $fileExtensions = [];

	protected $attrName;
	protected $quickMatch = '://';
	protected $regexp;
	protected $tagName;

	public function finalize(): void
	{
		$this->updateRegexp();
	}

	public function getJSParser()
	{
		return parent::getJSParser() . "\n" . file_get_contents(__DIR__ . '/Parser.js');
	}

	abstract protected function getTemplate(): string;

	/**
	* Creates the tag used by this plugin
	*
	* @return void
	*/
	protected function setUp(): void
	{
		$this->updateRegexp();

		if (isset($this->configurator->tags[$this->tagName]))
		{
			return;
		}

		// Create a tag
		$tag = $this->configurator->tags->add($this->tagName);

		// Add an attribute using the default url filter
		$filter = $this->configurator->attributeFilters['#url'];
		$tag->attributes->add($this->attrName)->filterChain->append($filter);

		// Set the default template
		$tag->template = $this->getTemplate();

		// Allow URL tags to be used as fallback
		$tag->rules->allowChild('URL');
	}

	protected function updateRegexp(): void
	{
		$this->regexp = '#\\bhttps?://[-.\\w]+/(?:[-+.:/\\w]|%[0-9a-f]{2}|\\(\\w+\\))+\\.' . RegexpBuilder::fromList($this->fileExtensions, ['caseInsensitive' => true, 'delimiter' => '#', 'unicode' => false]) . '(?!\\S)#i';
	}
}