<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use s9e\TextFormatter\Configurator\BundleGenerator;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterCollection;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RulesHelper;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\RulesGenerator;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Configurator\TemplateChecker;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
use s9e\TextFormatter\Configurator\UrlConfig;

class Configurator implements ConfigProvider
{
	/**
	* @var AttributeFilterCollection Dynamically-populated collection of AttributeFilter instances
	*/
	public $attributeFilters;

	/**
	* @var BundleGenerator Default bundle generator
	*/
	public $bundleGenerator;

	/**
	* @var JavaScript JavaScript manipulation object
	*/
	public $javascript;

	/**
	* @var PluginCollection Loaded plugins
	*/
	public $plugins;

	/**
	* @var array Array of variables that are available to the filters during parsing
	*/
	public $registeredVars;

	/**
	* @var RendererGenerator Generator used by $this->getRenderer()
	*/
	public $rendererGenerator;

	/**
	* @var Ruleset Rules that apply at the root of the text
	*/
	public $rootRules;

	/**
	* @var RulesGenerator Generator used by $this->getRenderer()
	*/
	public $rulesGenerator;

	/**
	* @var Stylesheet Stylesheet object
	*/
	public $stylesheet;

	/**
	* @var TagCollection Tags repository
	*/
	public $tags;

	/**
	* @var TemplateChecker Default template checker
	*/
	public $templateChecker;

	/**
	* @var TemplateNormalizer Default template normalizer
	*/
	public $templateNormalizer;

	/**
	* Constructor
	*
	* Prepares the collections that hold tags and filters, the UrlConfig object as well as the
	* various helpers required to generate a full config.
	*/
	public function __construct()
	{
		$this->attributeFilters   = new AttributeFilterCollection;
		$this->bundleGenerator    = new BundleGenerator($this);
		$this->javascript         = new JavaScript($this);
		$this->plugins            = new PluginCollection($this);
		$this->registeredVars     = ['urlConfig' => new UrlConfig];
		$this->rootRules          = new Ruleset;
		$this->rulesGenerator     = new RulesGenerator;
		$this->tags               = new TagCollection;
		$this->templateChecker    = new TemplateChecker;
		$this->templateNormalizer = new TemplateNormalizer;
		$this->stylesheet         = new Stylesheet($this);

		$this->setRendererGenerator('XSLT');
	}

	//==========================================================================
	// Magic methods
	//==========================================================================

	/**
	* Magic __get automatically loads plugins, returns registered vars
	*
	* @param  string $k Property name
	* @return mixed
	*/
	public function __get($k)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			return (isset($this->plugins[$k]))
			     ? $this->plugins[$k]
			     : $this->plugins->load($k);
		}

		if (isset($this->registeredVars[$k]))
		{
			return $this->registeredVars[$k];
		}

		throw new RuntimeException("Undefined property '" . __CLASS__ . '::$' . $k . "'");
	}

	/**
	* Magic __isset checks existence in the plugins collection and registered vars
	*
	* @param  string $k Property name
	* @return bool
	*/
	public function __isset($k)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			return isset($this->plugins[$k]);
		}

		return isset($this->registeredVars[$k]);
	}

	/**
	* Magic __set adds to the plugins collection, registers vars
	*
	* @param  string $k Property name
	* @param  mixed  $v Property value
	* @return mixed
	*/
	public function __set($k, $v)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			$this->plugins[$k] = $v;
		}
		else
		{
			$this->registeredVars[$k] = $v;
		}
	}

	/**
	* Magic __set removes plugins from the plugins collection, unregisters vars
	*
	* @param  string $k Property name
	* @return mixed
	*/
	public function __unset($k)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			unset($this->plugins[$k]);
		}
		else
		{
			unset($this->registeredVars[$k]);
		}
	}

	//==========================================================================
	// API
	//==========================================================================

	/**
	* Finalize this configuration and return all the relevant objects
	*
	* Options: (also see addHTMLRules() options)
	*
	*  - addHTML5Rules:    whether to call addHTML5Rules()
	*  - finalizeParser:   callback executed after the parser is created (gets the parser as arg)
	*  - finalizeRenderer: same with the renderer
	*  - optimizeConfig:   whether to optimize the parser's config. *DO NOT* use if the parser is
	*                      modified at runtime
	*  - returnParser:     whether to return an instance of Parser in the "parser" key
	*  - returnRenderer:   whether to return an instance of Renderer in the "renderer" key
	*
	* @param  array $options
	* @return array One "parser" element and one "renderer" element unless specified otherwise
	*/
	public function finalize(array $options = [])
	{
		$return = [];

		// Add default options
		$options += [
			'addHTML5Rules'  => true,
			'optimizeConfig' => false,
			'returnParser'   => true,
			'returnRenderer' => true
		];

		// Create a renderer as needed
		if ($options['returnRenderer'] || $options['addHTML5Rules'])
		{
			// Create a renderer
			$renderer = $this->getRenderer();

			// Execute the renderer callback if applicable
			if (isset($options['finalizeRenderer']))
			{
				$options['finalizeRenderer']($renderer);
			}

			if ($options['returnRenderer'])
			{
				$return['renderer'] = $renderer;
			}

			if ($options['addHTML5Rules'])
			{
				// Add the HTML5 rules. Pass the new renderer plus the other options
				$this->addHTML5Rules(['renderer' => $renderer] + $options);
			}
		}

		if ($options['returnParser'])
		{
			// Prepare the parser's config
			$config = $this->asConfig();
			ConfigHelper::filterVariants($config);

			if ($options['optimizeConfig'])
			{
				ConfigHelper::optimizeArray($config);
			}

			// Create a parser
			$parser = new Parser($config);

			// Execute the parser callback if applicable
			if (isset($options['finalizeParser']))
			{
				$options['finalizeParser']($parser);
			}

			$return['parser'] = $parser;
		}

		return $return;
	}

	/**
	* Return an instance of Parser based on the current config
	*
	* @return Parser
	*/
	public function getParser()
	{
		// Generate the config array
		$config = $this->asConfig();

		// Remove variants
		ConfigHelper::filterVariants($config);

		return new Parser($config);
	}

	/**
	* Return an instance of Renderer based on the current config
	*
	* NOTE: extra parameters are passed to the RendererGenerator's constructor
	*
	* @param  string $name Name of the RendererGenerator, e.g. "PHP"
	* @return Renderer
	*/
	public function getRenderer($name = null)
	{
		if (isset($name))
		{
			// Create a specific generator
			$rendererGenerator = $this->getRendererGenerator(func_get_args());
		}
		else
		{
			// Use the default renderer
			$rendererGenerator = $this->rendererGenerator;
		}

		return $rendererGenerator->getRenderer($this->stylesheet);
	}

	/**
	* Load a bundle into this configuration
	*
	* @param  string $bundleName Name of the bundle
	* @return void
	*/
	public function loadBundle($bundleName)
	{
		if (!preg_match('#^[A-Z][A-Za-z0-9]+$#D', $bundleName))
		{
			throw new InvalidArgumentException("Invalid bundle name '" . $bundleName . "'");
		}

		$className = __CLASS__ . '\\Bundles\\' . $bundleName;

		$bundle = new $className;
		$bundle->configure($this);
	}

	/**
	* Create and save a bundle based on this configuration
	*
	* @param  string $className Name of the bundle class
	* @param  string $filepath  Path where to save the bundle file
	* @param  array  $options   Options passed to the bundle generator
	* @return mixed             Number of bytes written, or FALSE
	*/
	public function saveBundle($className, $filepath, array $options = [])
	{
		$file = "<?php\n\n" . $this->bundleGenerator->generate($className, $options);

		return file_put_contents($filepath, $file);
	}

	/**
	* Add the rules that are generated based on HTML5 specs
	*
	* @see s9e\TextFormatter\ConfigBuilder\RulesGenerator
	*
	* @param  array $options Options passed to RulesGenerator::getRules()
	* @return void
	*/
	public function addHTML5Rules(array $options = [])
	{
		// Add the default options
		$options += ['rootRules' => $this->rootRules];

		// Finalize the plugins' config
		$this->plugins->finalize();

		// Normalize the tags' templates
		foreach ($this->tags as $tag)
		{
			$this->templateNormalizer->normalizeTag($tag);
		}

		// Get the rules
		$rules = $this->rulesGenerator->getRules($this->tags, $options);

		// Add the rules pertaining to the root
		$this->rootRules->merge($rules['root'], false);

		// Add the rules pertaining to each tag
		foreach ($rules['tags'] as $tagName => $tagRules)
		{
			$this->tags[$tagName]->rules->merge($tagRules, false);
		}
	}

	/**
	* Generate and return the complete config array
	*
	* @return array
	*/
	public function asConfig()
	{
		// Finalize the plugins' config
		$this->plugins->finalize();

		// Remove properties that shouldn't be turned into config arrays
		$properties = get_object_vars($this);
		unset($properties['attributeFilters']);
		unset($properties['bundleGenerator']);
		unset($properties['javascript']);
		unset($properties['rendererGenerator']);
		unset($properties['rulesGenerator']);
		unset($properties['templateChecker']);
		unset($properties['templateNormalizer']);
		unset($properties['stylesheet']);

		// Create the config array
		$config    = ConfigHelper::toArray($properties);
		$bitfields = RulesHelper::getBitfields($this->tags, $this->rootRules);

		// Save the root context
		$config['rootContext'] = $bitfields['root'];
		$config['rootContext']['flags'] = $config['rootRules']['flags'];

		// Make sure those keys exist even if they're empty
		$config += [
			'plugins'        => [],
			'registeredVars' => [],
			'tags'           => []
		];

		// Remove unused tags
		$config['tags'] = array_intersect_key($config['tags'], $bitfields['tags']);

		// Add the bitfield information to each tag
		foreach ($bitfields['tags'] as $tagName => $tagBitfields)
		{
			$config['tags'][$tagName] += $tagBitfields;
		}

		// Remove unused entries
		unset($config['rootRules']);

		return $config;
	}

	/**
	* Set the RendererGenerator instance used by this Configurator
	*
	* NOTE: extra parameters are passed to the RendererGenerator's constructor
	*
	* @param  string $name      Name of the RendererGenerator, e.g. "PHP"
	* @return RendererGenerator New instance of RendererGenerator
	*/
	public function setRendererGenerator($name)
	{
		$this->rendererGenerator = $this->getRendererGenerator(func_get_args());

		return $this->rendererGenerator;
	}

	/**
	* Generate and return an instance of RendererGenerator
	*
	* @param  array $args List of arguments, starting with the name of the generator
	* @return RendererGenerator
	*/
	protected function getRendererGenerator(array $args)
	{
		$className  = 's9e\\TextFormatter\\Configurator\\RendererGenerators\\' . $args[0];
		$reflection = new ReflectionClass($className);

		return $reflection->newInstanceArgs(array_slice($args, 1));
	}
}