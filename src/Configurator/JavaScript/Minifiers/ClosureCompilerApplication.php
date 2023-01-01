<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;

use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;

class ClosureCompilerApplication extends Minifier
{
	/**
	* @var string Command used to invoke the Closure Compiler application
	*/
	public $command;

	/**
	* @var string Closure Compiler's compilation level
	*/
	public $compilationLevel = 'ADVANCED_OPTIMIZATIONS';

	/**
	* @var bool Whether to exclude Closure Compiler's default externs
	*/
	public $excludeDefaultExterns = true;

	/**
	* @var string Extra options to be passed to the Closure Compiler application
	*/
	public $options = '';

	/**
	* Constructor
	*
	* @param string $command Command to execute
	*/
	public function __construct($command)
	{
		$this->command = $command;
	}

	/**
	* {@inheritdoc}
	*/
	public function getCacheDifferentiator()
	{
		$key = [
			$this->command,
			$this->compilationLevel,
			$this->excludeDefaultExterns,
			$this->options
		];
		if ($this->excludeDefaultExterns)
		{
			$key[] = file_get_contents(__DIR__ . '/../externs.application.js');
		}

		return $key;
	}

	/**
	* Compile given JavaScript source via the Closure Compiler application
	*
	* @param  string $src JavaScript source
	* @return string      Compiled source
	*/
	public function minify($src)
	{
		$options = ($this->options) ? ' ' . $this->options : '';

		// Add our custom externs if default externs are disabled
		if ($this->excludeDefaultExterns && $this->compilationLevel === 'ADVANCED_OPTIMIZATIONS')
		{
			$options .= ' --externs ' . __DIR__ . '/../externs.application.js --env=CUSTOM';
		}

		$crc     = crc32($src);
		$inFile  = sys_get_temp_dir() . '/' . $crc . '.js';
		$outFile = sys_get_temp_dir() . '/' . $crc . '.min.js';
		file_put_contents($inFile, $src);

		$cmd = $this->command
		     . ' --compilation_level ' . escapeshellarg($this->compilationLevel)
		     . $options
		     . ' --js ' . escapeshellarg($inFile)
		     . ' --js_output_file ' . escapeshellarg($outFile);

		exec($cmd . ' 2>&1', $output, $return);
		unlink($inFile);

		if (file_exists($outFile))
		{
			$src = trim(file_get_contents($outFile));
			unlink($outFile);
		}

		if (!empty($return))
		{
			throw new RuntimeException('An error occured during minification: ' . implode("\n", $output));
		}

		return $src;
	}
}