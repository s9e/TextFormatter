<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\FunctionProvider;
class ProgrammableCallback implements ConfigProvider
{
	protected $callback;
	protected $js = 'returnFalse';
	protected $params = array();
	protected $vars = array();
	public function __construct($callback)
	{
		if (!\is_callable($callback))
			throw new InvalidArgumentException(__METHOD__ . '() expects a callback');
		$this->callback = $this->normalizeCallback($callback);
		$this->autoloadJS();
	}
	public function addParameterByValue($paramValue)
	{
		$this->params[] = $paramValue;
		return $this;
	}
	public function addParameterByName($paramName)
	{
		if (\array_key_exists($paramName, $this->params))
			throw new InvalidArgumentException("Parameter '" . $paramName . "' already exists");
		$this->params[$paramName] = \null;
		return $this;
	}
	public function getCallback()
	{
		return $this->callback;
	}
	public function getJS()
	{
		return $this->js;
	}
	public function getVars()
	{
		return $this->vars;
	}
	public function resetParameters()
	{
		$this->params = array();
		return $this;
	}
	public function setJS($js)
	{
		$this->js = $js;
		return $this;
	}
	public function setVar($name, $value)
	{
		$this->vars[$name] = $value;
		return $this;
	}
	public function setVars(array $vars)
	{
		$this->vars = $vars;
		return $this;
	}
	public function asConfig()
	{
		$config = array('callback' => $this->callback);
		foreach ($this->params as $k => $v)
			if (\is_numeric($k))
				$config['params'][] = $v;
			elseif (isset($this->vars[$k]))
				$config['params'][] = $this->vars[$k];
			else
				$config['params'][$k] = \null;
		if (isset($config['params']))
			$config['params'] = ConfigHelper::toArray($config['params'], \true, \true);
		$config['js'] = new Code($this->js);
		return $config;
	}
	protected function autoloadJS()
	{
		if (!\is_string($this->callback))
			return;
		try
		{
			$this->js = FunctionProvider::get($this->callback);
		}
		catch (InvalidArgumentException $e)
		{
			}
	}
	protected function normalizeCallback($callback)
	{
		if (\is_array($callback) && \is_string($callback[0]))
			$callback = $callback[0] . '::' . $callback[1];
		if (\is_string($callback))
			$callback = \ltrim($callback, '\\');
		return $callback;
	}
}