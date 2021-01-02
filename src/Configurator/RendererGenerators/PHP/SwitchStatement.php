<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;

class SwitchStatement
{
	/**
	* @var array Dictionary of [value => php code]
	*/
	protected $branchesCode;

	/**
	* @var string PHP code for the default case
	*/
	protected $defaultCode;

	/**
	* @param array  $branchesCode Dictionary of [value => php code]
	* @param string $defaultCode  PHP code for the default case
	*/
	public function __construct(array $branchesCode, $defaultCode = '')
	{
		ksort($branchesCode);

		$this->branchesCode = $branchesCode;
		$this->defaultCode  = $defaultCode;
	}

	/**
	* Create and return the source code for a switch statement
	*
	* @param  string $expr         Expression used for the switch clause
	* @param  array  $branchesCode Dictionary of [value => php code]
	* @param  string $defaultCode  PHP code for the default case
	* @return string               PHP code
	*/
	public static function generate($expr, array $branchesCode, $defaultCode = '')
	{
		$switch = new static($branchesCode, $defaultCode);

		return $switch->getSource($expr);
	}

	/**
	* Return the source code for this switch statement
	*
	* @param  string $expr Expression used for the switch clause
	* @return string       PHP code
	*/
	protected function getSource($expr)
	{
		$php = 'switch(' . $expr . '){';
		foreach ($this->getValuesPerCodeBranch() as $branchCode => $values)
		{
			foreach ($values as $value)
			{
				$php .= 'case' . var_export((string) $value, true) . ':';
			}
			$php .= $branchCode . 'break;';
		}
		if ($this->defaultCode > '')
		{
			$php .= 'default:' . $this->defaultCode;
		}
		$php = preg_replace('(break;$)', '', $php) . '}';

		return $php;
	}

	/**
	* Group branches by their content and return the switch values for each branch
	*
	* @return array
	*/
	protected function getValuesPerCodeBranch()
	{
		$values = [];
		foreach ($this->branchesCode as $value => $branchCode)
		{
			$values[$branchCode][] = $value;
		}

		return $values;
	}
}