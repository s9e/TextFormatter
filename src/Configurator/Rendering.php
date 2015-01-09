<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ReflectionClass;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\TemplateParameterCollection;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Traits\Configurable;

class Rendering
{
	use Configurable;

	protected $configurator;

	protected $engine;

	protected $parameters;

	protected $type = 'html';

	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
		$this->parameters   = new TemplateParameterCollection;

		$this->setEngine('XSLT');
	}

	public function getAllParameters()
	{
		$params = [];
		foreach ($this->configurator->tags as $tag)
			if (isset($tag->template))
				foreach ($tag->template->getParameters() as $paramName)
					$params[$paramName] = '';

		$params = \iterator_to_array($this->parameters) + $params;

		\ksort($params);

		return $params;
	}

	public function getRenderer()
	{
		return $this->engine->getRenderer($this);
	}

	public function getTemplates()
	{
		$templates = [
			'br' => '<br/>',
			'e'  => '',
			'i'  => '',
			'p'  => '<p><xsl:apply-templates/></p>',
			's'  => ''
		];

		foreach ($this->configurator->tags as $tagName => $tag)
			if (isset($tag->template))
				$templates[$tagName] = (string) $tag->template;

		\ksort($templates);

		return $templates;
	}

	public function setEngine($engine)
	{
		if (!($engine instanceof RendererGenerator))
		{
			$className  = 's9e\\TextFormatter\\Configurator\\RendererGenerators\\' . $engine;
			$reflection = new ReflectionClass($className);

			$engine = $reflection->newInstanceArgs(\array_slice(\func_get_args(), 1));
		}

		$this->engine = $engine;

		return $engine;
	}

	protected function setType($type)
	{
		$type = \strtolower($type);

		if ($type !== 'html' && $type !== 'xhtml')
			throw new RuntimeException('Only HTML and XHTML rendering is supported');

		$this->type = $type;
	}
}