<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use ReflectionClass;
use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\TemplateParameterCollection;
use s9e\TextFormatter\Configurator\Traits\Configurable;

/**
* @property RendererGenerator $engine
* @property TemplateParameterCollection $parameters Parameters used by the renderer
*/
class Rendering
{
	use Configurable;

	/**
	* @var Configurator
	*/
	protected $configurator;

	/**
	* @var RendererGenerator
	*/
	protected $engine;

	/**
	* @var TemplateParameterCollection Parameters used by the renderer
	*/
	protected $parameters;

	/**
	* Constructor
	*
	* @param  Configurator $configurator
	*/
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
		$this->parameters   = new TemplateParameterCollection;
	}

	/**
	* Get all the parameters defined and/or used in all the templates
	*
	* @return array Associative array of parameters names and their default value
	*/
	public function getAllParameters()
	{
		// Collect parameters used in template
		$params = [];
		foreach ($this->configurator->tags as $tag)
		{
			if (isset($tag->template))
			{
				foreach ($tag->template->getParameters() as $paramName)
				{
					$params[$paramName] = '';
				}
			}
		}

		// Merge defined parameters and those collected from templates. Defined parameters take
		// precedence
		$params = iterator_to_array($this->parameters) + $params;

		// Sort parameters by name for consistency
		ksort($params);

		return $params;
	}

	/**
	* Return the renderer generator used by this instance
	*
	* @return RendererGenerator
	*/
	public function getEngine()
	{
		if (!isset($this->engine))
		{
			$this->setEngine('XSLT');
		}

		return $this->engine;
	}

	/**
	* Return an instance of Renderer based on the current config
	*
	* @return Renderer
	*/
	public function getRenderer()
	{
		return $this->getEngine()->getRenderer($this);
	}

	/**
	* Get the templates defined in all the targs
	*
	* @return array Associative array of template names and content
	*/
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
		{
			if (isset($tag->template))
			{
				$templates[$tagName] = (string) $tag->template;
			}
		}

		ksort($templates);

		return $templates;
	}

	/**
	* Set the RendererGenerator instance used
	*
	* NOTE: extra parameters are passed to the RendererGenerator's constructor
	*
	* @param  string|RendererGenerator $engine Engine name or instance of RendererGenerator
	* @return RendererGenerator                Instance of RendererGenerator
	*/
	public function setEngine($engine)
	{
		if (!($engine instanceof RendererGenerator))
		{
			$className  = 's9e\\TextFormatter\\Configurator\\RendererGenerators\\' . $engine;
			$reflection = new ReflectionClass($className);

			$engine = $reflection->newInstanceArgs(array_slice(func_get_args(), 1));
		}

		$this->engine = $engine;

		return $engine;
	}
}