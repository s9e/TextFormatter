<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP;
class SwitchStatement
{
	protected $branchesCode;
	protected $defaultCode;
	public function __construct(array $branchesCode, $defaultCode = '')
	{
		\ksort($branchesCode);
		$this->branchesCode = $branchesCode;
		$this->defaultCode  = $defaultCode;
	}
	public static function generate($expr, array $branchesCode, $defaultCode = '')
	{
		$switch = new static($branchesCode, $defaultCode);
		return $switch->getSource($expr);
	}
	protected function getSource($expr)
	{
		$php = 'switch(' . $expr . '){';
		foreach ($this->getValuesPerCodeBranch() as $branchCode => $values)
		{
			foreach ($values as $value)
				$php .= 'case' . \var_export((string) $value, \true) . ':';
			$php .= $branchCode . 'break;';
		}
		if ($this->defaultCode > '')
			$php .= 'default:' . $this->defaultCode;
		$php = \preg_replace('(break;$)', '', $php) . '}';
		return $php;
	}
	protected function getValuesPerCodeBranch()
	{
		$values = array();
		foreach ($this->branchesCode as $value => $branchCode)
			$values[$branchCode][] = $value;
		return $values;
	}
}