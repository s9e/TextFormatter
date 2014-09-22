<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Collections\TemplateParameterCollection;
use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Traits\Configurable;

/*
* @property RendererGenerator $engine
* @property TemplateParameterCollection $parameters Parameters used by the renderer
* @property string $type Output method, either "html" or "xhtml"
*/
class Rendering
{
	/*
	* Magic getter
	*
	* Will return $this->foo if it exists, then $this->getFoo() or will throw an exception if
	* neither exists
	*
	* @param  string $propName
	* @return mixed
	*/
	public function __get($propName)
	{
		$methodName = 'get' . \ucfirst($propName);

		// Look for a getter, e.g. getDefaultTemplate()
		if (\method_exists($this, $methodName))
			return $this->$methodName();

		if (!\property_exists($this, $propName))
			throw new RuntimeException("Property '" . $propName . "' does not exist");

		return $this->$propName;
	}

	/*
	* Magic setter
	*
	* Will call $this->setFoo($propValue) if it exists, otherwise it will set $this->foo.
	* If $this->foo is a NormalizedCollection, we do not replace it, instead we clear() it then
	* fill it back up. It will not overwrite an object with a different incompatible object (of a
	* different, non-extending class) and it will throw an exception if the PHP type cannot match
	* without incurring data loss.
	*
	* @param  string $propName
	* @param  mixed  $propValue
	* @return void
	*/
	public function __set($propName, $propValue)
	{
		$methodName = 'set' . \ucfirst($propName);

		// Look for a setter, e.g. setDefaultChildRule()
		if (\method_exists($this, $methodName))
		{
			$this->$methodName($propValue);

			return;
		}

		// If the property isn't already set, we just create/set it
		if (!isset($this->$propName))
		{
			$this->$propName = $propValue;

			return;
		}

		// If we're trying to replace a NormalizedCollection, instead we clear it then
		// iteratively set new values
		if ($this->$propName instanceof NormalizedCollection)
		{
			if (!\is_array($propValue)
			 && !($propValue instanceof Traversable))
				throw new InvalidArgumentException("Property '" . $propName . "' expects an array or a traversable object to be passed");

			$this->$propName->clear();

			foreach ($propValue as $k => $v)
				$this->$propName->set($k, $v);

			return;
		}

		// If this property is an object, test whether they are compatible. Otherwise, test if PHP
		// types are compatible
		if (\is_object($this->$propName))
		{
			if (!($propValue instanceof $this->$propName))
				throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of class '" . \get_class($this->$propName) . "' with instance of '" . \get_class($propValue) . "'");
		}
		else
		{
			// Test whether the PHP types are compatible
			$oldType = \gettype($this->$propName);
			$newType = \gettype($propValue);

			// If the property is a boolean, we'll accept "true" and "false" as strings
			if ($oldType === 'boolean')
				if ($propValue === 'false')
				{
					$newType   = 'boolean';
					$propValue = \false;
				}
				elseif ($propValue === 'true')
				{
					$newType   = 'boolean';
					$propValue = \true;
				}

			if ($oldType !== $newType)
			{
				// Test whether the PHP type roundtrip is lossless
				$tmp = $propValue;
				\settype($tmp, $oldType);
				\settype($tmp, $newType);

				if ($tmp !== $propValue)
					throw new InvalidArgumentException("Cannot replace property '" . $propName . "' of type " . $oldType . ' with value of type ' . $newType);

				// Finally, set the new value to the correct type
				\settype($propValue, $oldType);
			}
		}

		$this->$propName = $propValue;
	}

	/*
	* Test whether a property is set
	*
	* @param  string $propName
	* @return bool
	*/
	public function __isset($propName)
	{
		$methodName = 'isset' . \ucfirst($propName);

		if (\method_exists($this, $methodName))
			return $this->$methodName();

		return isset($this->$propName);
	}

	/*
	* Unset a property, if the class supports it
	*
	* @param  string $propName
	* @return void
	*/
	public function __unset($propName)
	{
		$methodName = 'unset' . \ucfirst($propName);

		if (\method_exists($this, $methodName))
		{
			$this->$methodName();

			return;
		}

		if (!isset($this->$propName))
			return;

		if ($this->$propName instanceof Collection)
		{
			$this->$propName->clear();

			return;
		}

		throw new RuntimeException("Property '" . $propName . "' cannot be unset");
	}

	/*
	* @var Configurator
	*/
	protected $configurator;

	/*
	* @var RendererGenerator
	*/
	protected $engine;

	/*
	* @var TemplateParameterCollection Parameters used by the renderer
	*/
	protected $parameters;

	/*
	* @var string Output method, either "html" or "xhtml"
	*/
	protected $type = 'html';

	/*
	* Constructor
	*
	* @param  Configurator $configurator
	* @return void
	*/
	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
		$this->parameters   = new TemplateParameterCollection;

		$this->setEngine('XSLT');
	}

	/*
	* Get all the parameters defined and/or used in all the templates
	*
	* @return array Associative array of parameters names and their default value
	*/
	public function getAllParameters()
	{
		// Collect parameters used in template
		$params = array();
		foreach ($this->configurator->tags as $tag)
			if (isset($tag->template))
				foreach ($tag->template->getParameters() as $paramName)
					$params[$paramName] = '';

		// Merge defined parameters and those collected from templates. Defined parameters take
		// precedence
		$params = \iterator_to_array($this->parameters) + $params;

		// Sort parameters by name for consistency
		\ksort($params);

		return $params;
	}

	/*
	* Return an instance of Renderer based on the current config
	*
	* @return Renderer
	*/
	public function getRenderer()
	{
		return $this->engine->getRenderer($this);
	}

	/*
	* Get the templates defined in all the targs
	*
	* @return array Associative array of template names and content
	*/
	public function getTemplates()
	{
		$templates = array(
			'br' => '<br/>',
			'e'  => '',
			'i'  => '',
			'p'  => '<p><xsl:apply-templates/></p>',
			's'  => ''
		);

		foreach ($this->configurator->tags as $tagName => $tag)
			if (isset($tag->template))
				$templates[$tagName] = (string) $tag->template;

		\ksort($templates);

		return $templates;
	}

	/*
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

			$engine = (\func_num_args() > 1) ? $reflection->newInstanceArgs(\array_slice(\func_get_args(), 1)) : $reflection->newInstance();
		}

		$this->engine = $engine;

		return $engine;
	}

	/*
	* Set the output type
	*
	* @param  string $type Either "html" or "xhtml"
	* @return void
	*/
	protected function setType($type)
	{
		$type = \strtolower($type);

		if ($type !== 'html' && $type !== 'xhtml')
			throw new RuntimeException('Only HTML and XHTML rendering is supported');

		$this->type = $type;
	}
}