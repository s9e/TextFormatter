<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator;

use function strtolower;
use s9e\TextFormatter\Configurator;

abstract class AbstractConfigurableHostHelper
{
	/**
	* @var Configurator
	*/
	protected Configurator $configurator;

	public function __construct(Configurator $configurator)
	{
		$this->configurator = $configurator;
	}

	public function addHost(string $host): void
	{
		$siteId = $this->getSiteId();
		if (!isset($this->configurator->registeredVars['MediaEmbed.sites'][$siteId]))
		{
			$this->configurator->MediaEmbed->add($siteId);
		}

		$host = strtolower($host);
		$this->configurator->registeredVars['MediaEmbed.hosts'][$host] = $siteId;
	}

	abstract protected function getSiteId(): string;
}