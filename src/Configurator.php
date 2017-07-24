<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\BundleGenerator;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterCollection;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\RulesHelper;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Configurator\RulesGenerator;
use s9e\TextFormatter\Configurator\TemplateChecker;
use s9e\TextFormatter\Configurator\TemplateNormalizer;
use s9e\TextFormatter\Configurator\UrlConfig;

/**
* @property Plugins\Autoemail\Configurator $Autoemail Autoemail plugin's configurator
* @property Plugins\Autoimage\Configurator $Autolink Autoimage plugin's configurator
* @property Plugins\Autolink\Configurator $Autolink Autolink plugin's configurator
* @property Plugins\Autovideo\Configurator $Autovideo Autovideo plugin's configurator
* @property Plugins\BBCodes\Configurator $BBCodes BBCodes plugin's configurator
* @property Plugins\Censor\Configurator $Censor Censor plugin's configurator
* @property Plugins\Emoji\Configurator $Emoji Emoji plugin's configurator
* @property Plugins\Emoticons\Configurator $Emoticons Emoticons plugin's configurator
* @property Plugins\Escaper\Configurator $Escaper Escaper plugin's configurator
* @property Plugins\FancyPants\Configurator $FancyPants FancyPants plugin's configurator
* @property Plugins\HTMLComments\Configurator $HTMLComments HTMLComments plugin's configurator
* @property Plugins\HTMLElements\Configurator $HTMLElements HTMLElements plugin's configurator
* @property Plugins\HTMLEntities\Configurator $HTMLEntities HTMLEntities plugin's configurator
* @property Plugins\Keywords\Configurator $Keywords Keywords plugin's configurator
* @property Plugins\Litedown\Configurator $Litedown Litedown plugin's configurator
* @property Plugins\MediaEmbed\Configurator $MediaEmbed MediaEmbed plugin's configurator
* @property Plugins\PipeTables\Configurator $PipeTables PipeTables plugin's configurator
* @property Plugins\Preg\Configurator $Preg Preg plugin's configurator
* @property UrlConfig $urlConfig Default URL config
*/
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
	* @var Rendering Rendering configuration
	*/
	public $rendering;

	/**
	* @var Ruleset Rules that apply at the root of the text
	*/
	public $rootRules;

	/**
	* @var RulesGenerator Generator used by $this->getRenderer()
	*/
	public $rulesGenerator;

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
		$this->plugins            = new PluginCollection($this);
		$this->registeredVars     = ['urlConfig' => new UrlConfig];
		$this->rendering          = new Rendering($this);
		$this->rootRules          = new Ruleset;
		$this->rulesGenerator     = new RulesGenerator;
		$this->tags               = new TagCollection;
		$this->templateChecker    = new TemplateChecker;
		$this->templateNormalizer = new TemplateNormalizer;
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
	* Enable the creation of a JavaScript parser
	*
	* @return void
	*/
	public function enableJavaScript()
	{
		if (!isset($this->javascript))
		{
			$this->javascript = new JavaScript($this);
		}
	}

	/**
	* Finalize this configuration and return all the relevant objects
	*
	* Options: (also see addHTMLRules() options)
	*
	*  - addHTML5Rules:    whether to call addHTML5Rules()
	*  - optimizeConfig:   whether to optimize the parser's config using references
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
			'optimizeConfig' => true,
			'returnJS'       => isset($this->javascript),
			'returnParser'   => true,
			'returnRenderer' => true
		];

		// Add the HTML5 rules if applicable
		if ($options['addHTML5Rules'])
		{
			$this->addHTML5Rules();
		}

		// Create a renderer as needed
		if ($options['returnRenderer'])
		{
			// Create a renderer
			$return['renderer'] = $this->rendering->getRenderer();
		}

		if ($options['returnJS'] || $options['returnParser'])
		{
			$config = $this->asConfig();

			if ($options['returnJS'])
			{
				$return['js'] = $this->javascript->getParser(ConfigHelper::filterConfig($config, 'JS'));
			}

			if ($options['returnParser'])
			{
				// Remove JS-specific data from the config
				$config = ConfigHelper::filterConfig($config, 'PHP');

				if ($options['optimizeConfig'])
				{
					ConfigHelper::optimizeArray($config);
				}

				// Create a parser
				$return['parser'] = new Parser($config);
			}
		}

		return $return;
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
	* @return bool              Whether the write succeeded
	*/
	public function saveBundle($className, $filepath, array $options = [])
	{
		$file = "<?php\n\n" . $this->bundleGenerator->generate($className, $options);

		return (file_put_contents($filepath, $file) !== false);
	}

	/**
	* Add the rules that are generated based on HTML5 specs
	*
	* @return void
	*/
	public function addHTML5Rules()
	{
		// Finalize the plugins' config
		$this->plugins->finalize();

		// Normalize the tags' templates
		foreach ($this->tags as $tag)
		{
			$this->templateNormalizer->normalizeTag($tag);
		}

		// Get the rules
		$rules = $this->rulesGenerator->getRules($this->tags);

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
		unset($properties['rendering']);
		unset($properties['rulesGenerator']);
		unset($properties['registeredVars']);
		unset($properties['templateChecker']);
		unset($properties['templateNormalizer']);
		unset($properties['stylesheet']);

		// Create the config array
		$config    = ConfigHelper::toArray($properties);
		$bitfields = RulesHelper::getBitfields($this->tags, $this->rootRules);

		// Save the root context
		$config['rootContext'] = $bitfields['root'];
		$config['rootContext']['flags'] = $config['rootRules']['flags'];

		// Save the registered vars (including the empty ones)
		$config['registeredVars'] = ConfigHelper::toArray($this->registeredVars, true);

		// Make sure those keys exist even if they're empty
		$config += [
			'plugins' => [],
			'tags'    => []
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
}