<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Code;
use s9e\TextFormatter\Configurator\JavaScript\FunctionProvider;

class ProgrammableCallback implements ConfigProvider
{
	protected $callback;

	protected $js = \null;

	protected $params = array();

	protected $vars = array();

	public function __construct($callback)
	{
		if (!\is_callable($callback))
			throw new InvalidArgumentException(__METHOD__ . '() expects a callback');

		if (\is_array($callback) && \is_string($callback[0]))
			$callback = $callback[0] . '::' . $callback[1];

		if (\is_string($callback))
			$callback = \ltrim($callback, '\\');

		$this->callback = $callback;
	}

	public function addParameterByValue($paramValue)
	{
		$this->params[] = $paramValue;

		return $this;
	}

	public function addParameterByName($paramName)
	{
		$this->params[$paramName] = \null;

		return $this;
	}

	public function getCallback()
	{
		return $this->callback;
	}

	public function getJS()
	{
		if (!isset($this->js) && \is_string($this->callback))
			try
			{
				return new Code(FunctionProvider::get($this->callback));
			}
			catch (InvalidArgumentException $e)
			{
				}

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
		if (!($js instanceof Code))
			$js = new Code($js);

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

		$js = $this->getJS();
		if (isset($js))
		{
			$config['js'] = new Variant;
			$config['js']->set('JS', $js);
		}

		return $config;
	}
}