<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MediaEmbed\Configurator\SiteHelpers;

use function array_keys, sort, strtolower;

abstract class AbstractConfigurableHostHelper extends AbstractSiteHelper
{
	public function addHost(string $host): void
	{
		$this->addHosts([$host]);
	}

	public function addHosts(array $hosts): void
	{
		$siteId = $this->getSiteId();
		if (!isset($this->configurator->registeredVars['MediaEmbed.sites'][$siteId]))
		{
			$this->configurator->MediaEmbed->add($siteId);
		}

		foreach ($hosts as $host)
		{
			$host = strtolower($host);
			$this->configurator->registeredVars['MediaEmbed.hosts'][$host] = $siteId;
		}
	}

	public function getHosts(): array
	{
		$hosts = array_keys(
			(array) ($this->configurator->registeredVars['MediaEmbed.hosts'] ?? []),
			$this->getSiteId(),
			true
		);
		sort($hosts, SORT_STRING);

		return $hosts;
	}

	abstract protected function getSiteId(): string;

	public function setHosts(array $hosts): void
	{
		$siteId = $this->getSiteId();
		if (!isset($this->configurator->registeredVars['MediaEmbed.sites'][$siteId]))
		{
			$this->configurator->MediaEmbed->add($siteId);
		}

		// Remove previously set hosts for this site
		foreach ($this->getHosts() as $host)
		{
			unset($this->configurator->registeredVars['MediaEmbed.hosts'][$host]);
		}

		$this->addHosts($hosts);
	}
}