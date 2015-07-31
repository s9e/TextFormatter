<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;

use RuntimeException;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;

class ClosureCompilerApplication extends Minifier
{
	/**
	* @var string Path to the Closure Compiler application
	*/
	public $closureCompilerBin;

	/**
	* @var string Closure Compiler's compilation level
	*/
	public $compilationLevel = 'ADVANCED_OPTIMIZATIONS';

	/**
	* @var bool Whether to exclude Closure Compiler's default externs
	*/
	public $excludeDefaultExterns = true;

	/**
	* @var string Path to java interpreter
	*/
	public $javaBin = 'java';

	/**
	* @var string Extra options to be passed to the Closure Compiler application
	*/
	public $options = '--use_types_for_optimization';

	/**
	* Constructor
	*
	* @param  string $filepath Path to the Closure Compiler .jar
	* @return void
	*/
	public function __construct($filepath)
	{
		if (!file_exists($filepath))
		{
			throw new RuntimeException('Cannot find Closure Compiler at ' . $filepath);
		}

		$this->closureCompilerBin = $filepath;
	}

	/**
	* {@inheritdoc}
	*/
	public function getCacheDifferentiator()
	{
		$key = array(
			$this->compilationLevel,
			$this->excludeDefaultExterns,
			$this->options,
			crc32(file_get_contents($this->closureCompilerBin))
		);

		if ($this->excludeDefaultExterns)
		{
			$key[] = file_get_contents(__DIR__ . '/../externs.js');
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
			$options .= ' --externs ' . __DIR__ . '/../externs.js --use_only_custom_externs';
		}

		$crc     = crc32($src);
		$inFile  = sys_get_temp_dir() . '/' . $crc . '.js';
		$outFile = sys_get_temp_dir() . '/' . $crc . '.min.js';

		file_put_contents($inFile, $src);

		$cmd = escapeshellcmd($this->javaBin)
		     . ' -jar ' . escapeshellarg($this->closureCompilerBin)
		     . ' --compilation_level ' . escapeshellarg($this->compilationLevel)
		     . $options
		     . ' --js ' . escapeshellarg($inFile)
		     . ' --js_output_file ' . escapeshellarg($outFile);

		exec($cmd . ' 2>/dev/null', $output, $return);
		unlink($inFile);

		if (file_exists($outFile))
		{
			$src = trim(file_get_contents($outFile));
			unlink($outFile);
		}

		if (!empty($return))
		{
			throw new RuntimeException('An error occured during minification');
		}

		return $src;
	}
}