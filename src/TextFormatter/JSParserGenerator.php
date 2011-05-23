<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

use RuntimeException;

class JSParserGenerator
{
	/**
	* @var ConfigBuilder
	*/
	protected $cb;

	/**
	* @var string Template source
	*/
	protected $tpl;

	/**
	* @var string Source being generated
	*/
	protected $src;

	/**
	* @var array Tags' config
	*/
	protected $tagsConfig;

	/**
	* List of Javascript reserved words
	*
	* @link https://developer.mozilla.org/en/JavaScript/Reference/Reserved_Words
	*
	* Also, Closure Compiler doesn't like "char"
	*/
	const RESERVED_WORDS_REGEXP =
		'#^(?:break|case|catch|continue|debugger|default|delete|do|else|finally|for|function|if|in|instanceof|new|return|switch|this|throw|try|typeof|var|void|while|with|class|enum|export|extends|import|super|implements|interface|let|package|private|protected|public|static|yield|char)$#D';

	/**
	* Ranges to be used in Javascript regexps instead of PCRE's Unicode properties
	*/
	static public $unicodeProps = array(
		'C' => '\\u0009-\\u000D\\u00AD\\u1FFF\\u200E\\u200F\\u206A-\\u206F\\u2427-\\u243F\\u27CB\\u2B4D-\\u2B4F\\u2E32-\\u2E7F\\u3FFF\\u5FFF\\u7FFF\\u9FFF\\uBFFF\\uDFFF\\uE000\\uE008\\uEFFF\\uFFF0-\\uFFF8\\uFFFF',
		'Cc' => '\\u0009-\\u000D',
		'Cf' => '\\u00AD\\u202A-\\u202E\\uE000\\u2061-\\u2064',
		'Cn' => '\\u10FF\\u23F4-\\u23FF\\u244B-\\u245F\\u27CD\\u2B5A-\\u2BFF\\u2FFF\\u4FFF\\u6FFF\\u8FFF\\uAFFF\\uCFFF\\uE000\\uE01F\\uFDD0-\\uFDEF\\uFFFE\\uFFFF',
		'L' => 'A-Fij\\u0149\\u0268\\u02B0-\\u02C1\\u02D0\\u02D1\\u02EC\\u0374\\u03D0-\\u03D2\\u03F0\\u03F1\\u03F4\\u03F5\\u0458\\u0640\\u06E5\\u06E6\\u07FA\\u0E40-\\u0E44\\u0EC0-\\u0EC4\\u115F\\u1160\\u1AA7\\u1C7B\\u1D40\\u1D45\\u1D49-\\u1D4C\\u1D50-\\u1D55\\u1D5C\\u1D62-\\u1D6A\\u1D6D\\u1D71\\u1D75\\u1D78\\u1D7C\\u1D9B-\\u1DBF\\u1ECB\\u2119-\\u211D\\u212F-\\u2131\\u2A70\\u2C7C\\u2C7D\\u2F80\\u30FC-\\u30FE\\u4E00-\\u9FCB\\uA60C\\uA717-\\uA71F\\uA788\\uAA70\\uAAB9\\uAAC0\\uAADD\\uFA0E\\uFA0F\\uFA13\\uFA14\\uFA21\\uFA27-\\uFA29\\uFA70-\\uFAD9\\uFF41-\\uFF46\\uFF9E-\\uFFA0',
		'L&' => 'A-Fij\\u0149\\u0268\\u03D0-\\u03D2\\u03F0\\u03F1\\u03F4\\u03F5\\u0458\\u1D42\\u1D48-\\u1D4C\\u1D50-\\u1D55\\u1D5C\\u1D62-\\u1D6A\\u1D6D\\u1D71\\u1D75\\u1D78\\u1D7C\\u1E2D\\u210A-\\u2113\\u212C\\u212D\\u213C-\\u213F\\uFF21-\\uFF26\\u2102\\u2115\\u2128\\u2145-\\u2149',
		'Lm' => '\\u02B0-\\u02C1\\u02D0\\u02D1\\u02EC\\u0374\\u0559\\u06E5\\u06E6\\u07FA\\u0E46\\u1AA7\\u1C7B\\u1D78\\u1DA4\\u2C7D\\u309D\\u309E\\u30FC-\\u30FE\\uA60C\\uA717-\\uA71F\\uA788\\uAA70\\uFF70\\u1843\\u2090-\\u2094\\u3031-\\u3035',
		'Lo' => '\\u0673\\u0EC0-\\u0EC4\\u17A3\\u17A4\\u2B74\\u3400-\\u4DB5\\uAAB5\\uAAB6\\uAABB\\uAABC\\uAAC2\\uFA0E\\uFA0F\\uFA13\\uFA14\\uFA21\\uFA27-\\uFA29\\uFA70-\\uFAD9\\u2000\\u3006',
		'M' => '\\u0300-\\u0357\\u0483-\\u0487\\u05A3-\\u05BD\\u05C1\\u05C2\\u05C4\\u05C5\\u0610-\\u061A\\u064B-\\u065F\\u06D6-\\u06DC\\u06E1-\\u06E4\\u06EA-\\u06ED\\u0730-\\u074A\\u07EB-\\u07F3\\u0818\\u0819\\u0825-\\u0827\\u0900-\\u0903\\u093B\\u093C\\u0951-\\u0957\\u0981-\\u0983\\u09BE-\\u09C4\\u09CB-\\u09CD\\u09E2\\u09E3\\u0A03\\u0A3E-\\u0A42\\u0A4B-\\u0A4D\\u0A70\\u0A71\\u0A81-\\u0A83\\u0ABE-\\u0AC5\\u0AC9\\u0ACD\\u0B01-\\u0B03\\u0B3E-\\u0B44\\u0B4B-\\u0B4D\\u0B57\\u0B82\\u0BBE-\\u0BC2\\u0BCA-\\u0BCD\\u0C01-\\u0C03\\u0C41-\\u0C44\\u0C4A-\\u0C4D\\u0C62\\u0C63\\u0CBC\\u0CBF-\\u0CC4\\u0CC7\\u0CC8\\u0CCC\\u0CCD\\u0CE2\\u0CE3\\u0D3E-\\u0D44\\u0D4A-\\u0D4D\\u0D62\\u0D63\\u0DCA\\u0DCF-\\u0DD4\\u0DD8-\\u0DDF\\u0E31\\u0E47-\\u0E4E\\u0EB4-\\u0EB9\\u0EC8-\\u0ECD\\u0F35\\u0F39\\u0F71-\\u0F84\\u0F8D-\\u0F97\\u0FC6\\u102D-\\u1030\\u103B-\\u103E\\u1087-\\u108D\\u109A-\\u109D\\u110B\\u17B6-\\u17D3\\u180B-\\u180D\\u1929-\\u192B\\u19B0-\\u19C0\\u1A17-\\u1A1B\\u1A56-\\u1A5E\\u1A62-\\u1A7C\\u1B00-\\u1B04\\u1B35-\\u1B44\\u1B80-\\u1B82\\u1BA2-\\u1BAA\\u1BE8-\\u1BF1\\u1C2C-\\u1C37\\u1CD4-\\u1CE8\\u1CF2\\u1D17\\u1D18\\u1DC4-\\u1DCF\\u20D0-\\u20DC\\u20E5\\u20E6\\u2CEF-\\u2CF1\\u302A-\\u302F\\uA66F\\uA6F0\\uA6F1\\uA825-\\uA827\\uA8B4-\\uA8C4\\uA926-\\uA92D\\uA952\\uA953\\uA983\\uA9B4-\\uA9C0\\uAA2F-\\uAA36\\uAA4C\\uAA4D\\uAAB0\\uAAB7\\uAAB8\\uAABF\\uABE3-\\uABEA\\uABED\\uFB1E\\uFE20-\\uFE26',
		'Mc' => '\\u0903\\u0949-\\u094C\\u09BE-\\u09C0\\u09CB\\u09CC\\u0A03\\u0A83\\u0AC9\\u0B02\\u0B03\\u0B40\\u0B4B\\u0B4C\\u0BBE\\u0BBF\\u0BC6-\\u0BC8\\u0BD7\\u0C41-\\u0C44\\u0CBE\\u0CC2\\u0CCA\\u0CCB\\u0D02\\u0D03\\u0D3E-\\u0D40\\u0D4A-\\u0D4C\\u0D82\\u0D83\\u0DCF-\\u0DD1\\u0DDF\\u0F3E\\u0F3F\\u102B\\u102C\\u1087-\\u108C\\u109A-\\u109C\\u17B6\\u17C7\\u17C8\\u19B0-\\u19C0\\u1A19-\\u1A1B\\u1A57\\u1A63\\u1A64\\u1B04\\u1B3B\\u1B43\\u1B44\\u1BA1\\u1BAA\\u1BEA-\\u1BEC\\u1C24-\\u1C2B\\u1CE1\\u1D16\\uA827\\uA8B4-\\uA8C3\\uA953\\uA9B4\\uA9B5\\uA9BD-\\uA9C0\\uAA33\\uAA34\\uAA7B\\uABE6\\uABE7\\uABEC',
		'Mn' => '\\u0300-\\u0357\\u0483-\\u0487\\u05A3-\\u05BD\\u05C1\\u05C2\\u05C4\\u05C5\\u0610-\\u061A\\u064B-\\u065F\\u06D6-\\u06DC\\u06E1-\\u06E4\\u06EA-\\u06ED\\u0730-\\u074A\\u07EB-\\u07F3\\u0818\\u0819\\u0825-\\u0827\\u0900-\\u0902\\u093C\\u094D\\u0955-\\u0957\\u0981\\u09C1-\\u09C4\\u09E2\\u09E3\\u0A3C\\u0A47\\u0A48\\u0A4D\\u0A70\\u0A71\\u0A81\\u0A82\\u0AC1-\\u0AC5\\u0ACD\\u0B01\\u0B3F\\u0B4D\\u0B62\\u0B63\\u0BC0\\u0C3E-\\u0C40\\u0C4A-\\u0C4D\\u0C62\\u0C63\\u0CBF\\u0CCC\\u0CCD\\u0D41-\\u0D44\\u0D62\\u0D63\\u0DD2-\\u0DD4\\u0E31\\u0E47-\\u0E4E\\u0EB4-\\u0EB9\\u0EC8-\\u0ECD\\u0F35\\u0F39\\u0F77\\u0F80-\\u0F84\\u0F8D-\\u0F97\\u0FC6\\u1039\\u103A\\u105E-\\u1060\\u109D\\u110B\\u17B7-\\u17BD\\u17C9-\\u17D3\\u180B-\\u180D\\u1939-\\u193B\\u1A56\\u1A62\\u1A73-\\u1A7C\\u1B00-\\u1B03\\u1B36-\\u1B3A\\u1B42\\u1B80\\u1B81\\u1BA8\\u1BA9\\u1BED\\u1C2C-\\u1C33\\u1C36\\u1C37\\u1CD4-\\u1CE0\\u1CED\\u1D17\\u1D18\\u1DC4-\\u1DCF\\u20D0-\\u20DC\\u2CEF-\\u2CF1\\u302A-\\u302F\\uA66F\\uA6F0\\uA6F1\\uA8C4\\uA926-\\uA92D\\uA980-\\uA982\\uA9B6-\\uA9B9\\uAA29-\\uAA2E\\uAA35\\uAA36\\uAA4C\\uAAB2-\\uAAB4\\uAABE\\uAABF\\uABE5\\uABED\\uFB1E\\uFE20-\\uFE26',
		'N' => '0-9\\u1D7C\\u2170-\\u217F\\uFF10-\\uFF19',
		'Nd' => '0-9\\uFF10-\\uFF19',
		'Nl' => '\\u2160-\\u217F\\u3007',
		'No' => '\\u19DA',
		'P' => '\\!-#\'-\\*\\--/\\?@\\\\\\]\\}\\u00AB\\u00BB\\u0387\\u0589\\u058A\\u05C3\\u061B\\u06D4\\u0700-\\u070A\\u07F8\\u07F9\\u0964\\u0965\\u0F08\\u103D\\u10A5\\u110B\\u16EB-\\u16ED\\u17DA\\u1B5A\\u1B5B\\u1B5E\\u1B5F\\u1C3B-\\u1C3F\\u1CD3\\u201B-\\u201D\\u203A-\\u203E\\u207D\\u232A\\u276B-\\u276D\\u27C5\\u27C6\\u27EB-\\u27EF\\u298B-\\u298D\\u29D8-\\u29DB\\u29FD\\u2E0A-\\u2E16\\u2E1B-\\u2E1F\\u2E2E\\u300B-\\u300D\\u301A-\\u301D\\u30FB\\uA4FF\\uA60E\\uA60F\\uA6F3-\\uA6F7\\uA8CE\\uA8CF\\uA92F\\uA9C8\\uA9C9\\uAADF\\uFD3E\\uFD3F\\uFE41-\\uFE46\\uFE52\\uFE56-\\uFE58\\uFE63\\uFF01\\uFF02\\uFF0C-\\uFF0E\\uFF1F\\uFF61-\\uFF65',
		'Pc' => '\\u2040',
		'Pd' => '\\-\\u05BE\\u2E1A\\u30A0\\uFE58\\uFF0D',
		'Pe' => '\\)\\}\\u208E\\u276B\\u276F\\u27E7\\u27EB\\u27EF\\u298C\\u29D9\\u29FD\\u300D\\u301B\\uFD3F\\uFE44',
		'Pf' => '\\u00BB\\u203A\\u2E05\\u2E0D\\u2E21',
		'Pi' => '\\u00AB\\u201F\\u2E02\\u2E09\\u2E1C',
		'Po' => '\\!-#\',\\./\\?@\\u00A1\\u00BF\\u0387\\u055E\\u05C3\\u061B\\u06D4\\u0700-\\u070A\\u07F8\\u07F9\\u085E\\u0E5A\\u0E5B\\u0F0D-\\u0F12\\u104A\\u104B\\u10B3\\u166D\\u166E\\u17D4-\\u17D6\\u1AA8-\\u1AAB\\u1B5D-\\u1B5F\\u1C3B-\\u1C3F\\u1CD3\\u203C\\u203D\\u2E00\\u2E01\\u2E0B\\u2E18\\u2E19\\u2E1E\\u2E1F\\u2E2E\\u30FB\\uA4FF\\uA60E\\uA60F\\uA6F3-\\uA6F7\\uA8CE\\uA8CF\\uA92F\\uA9C8\\uA9C9\\uAADF\\uFE45\\uFE46\\uFE52\\uFE56\\uFE57\\uFE68\\uFF02\\uFF0C\\uFF1A\\uFF1B\\uFF3C\\uFF64\\uFF65',
		'Ps' => '\\(\\{\\u207D\\u276A\\u27C5\\u27EC\\u298B\\u298F\\u29DA\\u300A\\u301A\\uFD3E\\uFE43\\u2045\\u2768\\u2772\\u2983\\u2987\\u2991\\u2995\\u3008\\u3014\\u3018',
		'S' => '\\$\\<-\\>`~\\u00A6-\\u00A9\\u00AE-\\u00B1\\u00B6\\u00D7\\u02C2-\\u02C5\\u02E5-\\u02EB\\u02EF-\\u02FF\\u0384\\u0385\\u1FBF-\\u1FC1\\u1FDD-\\u1FDF\\u1FFD\\u1FFE\\u208B\\u219C-\\u22FF\\u230C-\\u231F\\u237C-\\u23E1\\u24B6-\\u24E9\\u25A0\\u25A1\\u25B7-\\u26FF\\u27C0-\\u27C4\\u27CC\\u27F0-\\u28FF\\u29DC-\\u29FB\\u2B00-\\u2B4C\\u2F00-\\u2FD5\\u2FF2-\\u2FFB\\uA720\\uA721\\uFF40\\u2044\\u2118\\u2190-\\u2199\\u2300-\\u2307\\u2322-\\u2328\\u2605\\u2606\\u2642\\u2701-\\u2767\\u3012\\u3013\\u212E\\u23E2-\\u23F3\\u25E4\\u2E80-\\u2E99',
		'Sc' => '\\$',
		'Sk' => '\\^\\u00A8\\u00B4\\u02C2-\\u02C5\\u02E5-\\u02EB\\u02EF-\\u02FF\\u0384\\u0385\\u1FBF-\\u1FC1\\u1FDD-\\u1FDF\\u1FFD\\u1FFE\\uA720\\uA721\\uFF40',
		'Sm' => '\\+\\|\\u00AC\\u00D7\\u207B\\u219A\\u219B\\u21A3\\u21AE\\u21D2\\u21F4-\\u22FF\\u237C\\u23DC-\\u23E1\\u25C1\\u266F\\u27C7-\\u27CA\\u27CE-\\u27E5\\u2999-\\u29D7\\u29FE-\\u2AFF\\u2B47-\\u2B4C',
		'So' => '\\u00A6\\u00A7\\u00AE\\u00B6\\u219C-\\u219F\\u21A4\\u21A5\\u21A7-\\u21AD\\u21B0\\u21B1\\u21BC-\\u21CD\\u21D3\\u21D5-\\u21F3\\u232B-\\u237B\\u23B4-\\u23DB\\u23E2-\\u23F3\\u24B6-\\u24E9\\u25A0\\u25A1\\u25B8-\\u25C0\\u25C6\\u25C7\\u25CF-\\u25D3\\u25E4\\u2600-\\u266E\\u2794-\\u27BF\\u2B00-\\u2B2F\\u2B50-\\u2B59\\u2E9B-\\u2EF3\\u2FF0-\\u2FFB\\u3020',
		'Z' => ' \\u180E\\u202F\\u1680\\u2029',
		'Zl' => '\\u2028',
		'Zp' => '\\u2029',
		'Zs' => ' \\u1680\\u180E\\u202F'
	);

	/**
	* 
	*
	* @return void
	*/
	public function __construct(ConfigBuilder $cb)
	{
		$this->cb = $cb;

		$this->tpl = file_get_contents(__DIR__ . '/TextFormatter.js');
	}

	/**
	* 
	*
	* @return string
	*/
	public function get(array $options = array())
	{
		$options += array(
			'compilation'        => 'none',
			'disableLogTypes'    => array(),
			'removeDeadCode'     => true,
			'escapeScriptEndTag' => true
		);

		$this->tagsConfig = $this->cb->getTagsConfig(true);
		$this->src = $this->tpl;

		if ($options['removeDeadCode'])
		{
			$this->removeDeadCode();
		}

		$this->injectTagsConfig();
		$this->injectPlugins();
		$this->injectFiltersConfig();
		$this->injectCallbacks();

		/**
		* Turn off logging selectively
		*/
		if ($options['disableLogTypes'])
		{
			$this->src = preg_replace(
				"#\\n\\s*(?=log\\('(?:" . implode('|', $options['disableLogTypes']) . ")',)#",
				'${0}0&&',
				$this->src
			);
		}

		$this->injectXSL();

		if ($options['compilation'] !== 'none')
		{
			$this->compile($options['compilation']);
		}

		if ($options['escapeScriptEndTag'])
		{
			$this->src = preg_replace('#</(script)#i', '<\\/$1', $this->src);
		}

		return $this->src;
	}

	/**
	* Compile/minimize the JS source
	*
	* @param string $level Level to be passed to the Google Closure Compiler service
	*/
	protected function compile($level)
	{
		$content = http_build_query(array(
			'js_code' => $this->src,
			'compilation_level' => $level,
			'output_format' => 'text',
//			'formatting' => 'pretty_print',
			'output_info' => 'compiled_code'
		));

		$this->src = file_get_contents(
			'http://closure-compiler.appspot.com/compile',
			false,
			stream_context_create(array(
				'http' => array(
					'method'  => 'POST',
					'header'  => "Connection: close\r\n"
					           . "Content-length: " . strlen($content) . "\r\n"
					           . "Content-type: application/x-www-form-urlencoded\r\n",
					'content' => $content
				)
			))
		);
	}

	/**
	* Remove JS code that is not going to be used
	*/
	protected function removeDeadCode()
	{
		$this->removeDeadRules();
		$this->removeAttributesProcessing();
		$this->removeDeadFilters();
		$this->removeWhitespaceTrimming();
		$this->removeCompoundAttritutesSplitting();
		$this->removePhaseCallbacksProcessing();
		$this->removeAttributesDefaultValueProcessing();
		$this->removeRequiredAttributesProcessing();
	}

	/**
	* Remove the code related to attributes' default value if no attribute has a default value
	*/
	protected function removeAttributesDefaultValueProcessing()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (isset($attrConf['defaultValue']))
					{
						return;
					}
				}
			}
		}

		$this->removeFunctions('addDefaultAttributeValuesToCurrentTag');
	}

	/**
	* Remove the code checks whether all required attributes have been filled in for a tag, if
	* there no required attribute for any tag
	*/
	protected function removeRequiredAttributesProcessing()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (!empty($attrConf['isRequired']))
					{
						return;
					}
				}
			}
		}

		$this->removeFunctions('currentTagRequiresMissingAttribute');
	}

	/**
	* Remove the code related to preFilter/postFilter callbacks
	*/
	protected function removePhaseCallbacksProcessing()
	{
		$remove = array(
			'applyTagPreFilterCallbacks' => 1,
			'applyTagPostFilterCallbacks' => 1,
			'applyAttributePreFilterCallbacks' => 1,
			'applyAttributePostFilterCallbacks' => 1
		);

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['preFilter']))
			{
				unset($remove['applyTagPreFilterCallbacks']);
			}

			if (!empty($tagConfig['postFilter']))
			{
				unset($remove['applyTagPostFilterCallbacks']);
			}

			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (!empty($attrConf['preFilter']))
					{
						unset($remove['applyAttributePreFilterCallbacks']);
					}

					if (!empty($attrConf['postFilter']))
					{
						unset($remove['applyAttributePostFilterCallbacks']);
					}
				}
			}
		}

		if (count($remove) === 4)
		{
			$remove['applyCallback'] = 1;
		}

		if ($remove)
		{
			$this->removeFunctions(implode('|', array_keys($remove)));
		}
	}

	/**
	* Remove JS code related to rules that are not used
	*/
	protected function removeDeadRules()
	{
		$rules = array(
			'closeParent',
			'closeAscendant',
			'requireParent',
			'requireAscendant'
		);

		$remove = array();

		foreach ($rules as $rule)
		{
			foreach ($this->tagsConfig as $tagConfig)
			{
				if (!empty($tagConfig['rules'][$rule]))
				{
					continue 2;
				}
			}

			$remove[] = $rule;
		}

		if ($remove)
		{
			$this->removeFunctions(implode('|', $remove));
		}
	}

	/**
	* Remove JS code related to attributes if no attributes exist
	*/
	protected function removeAttributesProcessing()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				return;
			}
		}

		$this->removeFunctions('filterAttributes|filter');
	}

	/**
	* Remove JS code related to compound attributes if none exist
	*/
	protected function removeCompoundAttritutesSplitting()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrConf)
				{
					if ($attrConf['type'] === 'compound')
					{
						return;
					}
				}
			}
		}

		$this->removeFunctions('splitCompoundAttributes');
	}

	/**
	* Remove JS code related to filters that are not used
	*/
	protected function removeDeadFilters()
	{
		$keepFilters = array();

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrConf)
				{
					$keepFilters[$attrConf['type']] = 1;
				}
			}
		}
		$keepFilters = array_keys($keepFilters);

		$this->src = preg_replace_callback(
			"#\n\tfunction filter\\(.*?\n\t\}#s",
			function ($functionBlock) use ($keepFilters)
			{
				return preg_replace_callback(
					"#\n\t\t\tcase .*?\n\t\t\t\treturn[^\n]+\n#s",
					function ($caseBlock) use ($keepFilters)
					{
						preg_match_all("#\n\t\t\tcase '([a-z]+)#", $caseBlock[0], $m);

						if (array_intersect($m[1], $keepFilters))
						{
							// at least one of those case can happen, we keep the whole block
							return $caseBlock[0];
						}

						// remove block
						return '';
					},
					$functionBlock[0]
				);
			},
			$this->src
		);
	}

	/**
	* Remove the content of some JS functions from the source
	*
	* @param string  $regexp Regexp used to match the function's name
	*/
	protected function removeFunctions($regexp)
	{
		$this->src = preg_replace(
			'#(\\n\\t+function\\s+(?:' . $regexp . ')\\(.*?\\)(\\n\\t+)\\{).*?(\\2\\})#s',
			'$1$3',
			$this->src
		);
	}

	protected function injectTagsConfig()
	{
		$this->src = str_replace(
			'tagsConfig = {/* DO NOT EDIT*/}',
			'tagsConfig = ' . $this->generateTagsConfig(),
			$this->src
		);
	}

	protected function injectPlugins()
	{
		$pluginParsers = array();
		$pluginsConfig = array();

		foreach ($this->cb->getJSPlugins() as $pluginName => $plugin)
		{
			$js = self::encodeConfig(
				$plugin['config'],
				$plugin['meta']
			);

			$pluginsConfig[] = json_encode($pluginName) . ':' . $js;
			$pluginParsers[] =
				json_encode($pluginName) . ':function(text,matches){/** @const */var config=pluginsConfig["' . $pluginName . '"];' . $plugin['parser'] . '}';
		}

		$this->src = str_replace(
			'pluginsConfig = {/* DO NOT EDIT*/}',
			'pluginsConfig = {' . implode(',', $pluginsConfig) . '}',
			$this->src
		);

		$this->src = str_replace(
			'pluginParsers = {/* DO NOT EDIT*/}',
			'pluginParsers = {' . implode(',', $pluginParsers) . '}',
			$this->src
		);
	}

	protected function injectPluginParsers()
	{
		$this->src = str_replace(
			'pluginParsers = {/* DO NOT EDIT*/}',
			'pluginParsers = {' . $this->generatePluginParsers() . '}',
			$this->src
		);
	}

	protected function injectPluginsConfig()
	{
		$this->src = str_replace(
			'pluginsConfig = {/* DO NOT EDIT*/}',
			'pluginsConfig = {' . $this->generatePluginsConfig() . '}',
			$this->src
		);
	}

	protected function injectFiltersConfig()
	{
		$this->src = str_replace(
			'filtersConfig = {/* DO NOT EDIT*/}',
			'filtersConfig = ' . $this->generateFiltersConfig(),
			$this->src
		);
	}

	protected function injectCallbacks()
	{
		$this->src = str_replace(
			'callbacks = {/* DO NOT EDIT*/}',
			'callbacks = ' . $this->generateCallbacks(),
			$this->src
		);
	}

	protected function generateCallbacks()
	{
		$usedCallbacks = array();

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['preFilter']))
			{
				foreach ($tagConfig['preFilter'] as $callbackConf)
				{
					$usedCallbacks[] = $callbackConf['callback'];
				}
			}

			if (!empty($tagConfig['postFilter']))
			{
				foreach ($tagConfig['postFilter'] as $callbackConf)
				{
					$usedCallbacks[] = $callbackConf['callback'];
				}
			}

			if (!empty($tagConfig['attrs']))
			{
				foreach ($tagConfig['attrs'] as $attrName => $attrConf)
				{
					if (!empty($attrConf['preFilter']))
					{
						foreach ($attrConf['preFilter'] as $callbackConf)
						{
							$usedCallbacks[] = $callbackConf['callback'];
						}
					}

					if (!empty($attrConf['postFilter']))
					{
						foreach ($attrConf['postFilter'] as $callbackConf)
						{
							$usedCallbacks[] = $callbackConf['callback'];
						}
					}
				}
			}
		}

		$jsCallbacks = array();

		foreach (array_unique($usedCallbacks) as $funcName)
		{
			if (!preg_match('#^[a-z_0-9]+$#Di', $funcName))
			{
				/**
				* This cannot actually happen because callbacks are validated in ConfigBuilder.
				* HOWEVER, if there was a way to get around this validation, this method could be
				* used to get the content of any file in the filesystem, so we're still validating
				* the callback name here as a failsafe.
				*/
				throw new RuntimeException("Invalid callback name '" . $funcName . "'");
			}

			$filepath = __DIR__ . '/jsFunctions/' . $funcName . '.js';
			if (file_exists($filepath))
			{
				$jsCallbacks[] = json_encode($funcName) . ':' . file_get_contents($filepath);
			}
		}

		return '{' . implode(',', $jsCallbacks) . '}';
	}

	/**
	* Kind of hardcoding stuff here, will need to be cleaned up at some point
	*/
	protected function generateFiltersConfig()
	{
		$filtersConfig = $this->cb->getFiltersConfig();

		if (isset($filtersConfig['url']['disallowedHosts']))
		{
			// replace the unsupported lookbehind assertion with a non-capturing subpattern
			$filtersConfig['url']['disallowedHosts'] = str_replace(
				'(?<![^\\.])',
				'(?:^|\\.)',
				$filtersConfig['url']['disallowedHosts']
			);
		}

		return self::encode(
			$filtersConfig,
			array(
				'preserveKeys' => array(
					array(true)
				),
				'isRegexp' => array(
					array('url', 'allowedSchemes'),
					array('url', 'disallowedHosts')
				)
			)
		);
	}

	protected function generateTagsConfig()
	{
		$tagsConfig = $this->tagsConfig;

		foreach ($tagsConfig as $tagName => &$tagConfig)
		{
			$this->fixTagAttributesRegexp($tagConfig);

			/**
			* Sort tags alphabetically. It can improve the compression if the source gets gzip'ed
			*/
			ksort($tagConfig['allow']);

			if (!empty($tagConfig['rules']))
			{
				foreach ($tagConfig['rules'] as $rule => &$tagNames)
				{
					/**
					* The PHP parser uses the keys, but the JS parser uses an Array instead
					*/
					$tagNames = array_keys($tagNames);
				}
				unset($tagNames);
			}
		}
		unset($tagConfig);

		return self::encode(
			$tagsConfig,
			array(
				'preserveKeys' => array(
					array(true),
					array(true, 'allow', true),
					array(true, 'attrs', true)
				),
				'isRegexp' => array(
					array(true, 'attrs', true, 'regexp')
				)
			)
		);
	}

	protected function fixTagAttributesRegexp(array &$tagConfig)
	{
		if (empty($tagConfig['attrs']))
		{
			return;
		}

		foreach ($tagConfig['attrs'] as &$attrConf)
		{
			if (!isset($attrConf['regexp']))
			{
				continue;
			}

			$backslashes = '(?<!\\\\)(?<backslashes>(?:\\\\\\\\)*)';
			$nonCapturing = '(?<nonCapturing>\\?[a-zA-Z]*:)';
			$name = '(?<name>[A-Za-z_0-9]+)';
			$namedCapture = implode('|', array(
				'\\?P?<' . $name . '>',
				"\\?'" . $name . "'"
			));

			$k = 0;
			$attrConf['regexp'] = preg_replace_callback(
				'#' . $backslashes . '\\((?J:' . $nonCapturing . '|' . $namedCapture . ')#',
				function ($m) use (&$attrConf, &$k)
				{
					if ($m['nonCapturing'])
					{
						return $m[0];
					}

					$attrConf['regexpMap'][$m['name']] = ++$k;

					return $m['backslashes'] . '(';
				},
				$attrConf['regexp']
			);
		}
	}

	protected function generatePluginParsers()
	{
		$pluginParsers = array();

		foreach ($this->cb->getLoadedPlugins() as $pluginName => $plugin)
		{
			$js = $plugin->getJSParser();

			if (!$js)
			{
				continue;
			}

			$pluginParsers[] = json_encode($pluginName) . ':function(text,matches){' . $js . '}';
		}

		return implode(',', $pluginParsers);
	}

	protected function generatePluginsConfig()
	{
		$pluginsConfig = array();

		foreach ($this->cb->getLoadedPlugins() as $pluginName => $plugin)
		{
			$js = self::encodeConfig(
				$plugin->getJSConfig(),
				$plugin->getJSConfigMeta()
			);

			$pluginsConfig[] = json_encode($pluginName) . ':' . $js;
		}

		return implode(',', $pluginsConfig);
	}

	/**
	* Remove JS code related to whitespace trimming if not used
	*/
	protected function removeWhitespaceTrimming()
	{
		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['trimBefore'])
			 || !empty($tagConfig['trimAfter']))
			{
				return;
			}
		}

		$this->removeFunctions('addTrimmingInfoToTag');
	}

	protected function injectXSL()
	{
		$pos = 58 + strpos(
			$this->src,
			"xslt['importStylesheet'](new DOMParser().parseFromString('', 'text/xml'));"
		);

		$this->src = substr($this->src, 0, $pos)
		           . addcslashes($this->cb->getXSL(), "'\\\r\n")
		           . substr($this->src, $pos);
	}

	static public function encodeConfig(array $pluginConfig, array $struct)
	{
		unset(
			$pluginConfig['parserClassName'],
			$pluginConfig['parserFilepath']
		);

		// mark the plugin's regexp(s) as global regexps
		if (!empty($pluginConfig['regexp']))
		{
			$keypath = (is_array($pluginConfig['regexp']))
			         ? array('regexp', true)
			         : array('regexp');

			$struct = array_merge_recursive($struct, array(
				'isGlobalRegexp' => array(
					$keypath
				)
			));
		}

		return self::encode($pluginConfig, $struct);
	}

	static public function encode(array $arr, array $struct = array())
	{
		/**
		* Replace booleans with 1/0
		*/
		array_walk_recursive($arr, function(&$v)
		{
			if (is_bool($v))
			{
				$v = (int) $v;
			}
		});

		return self::encodeArray($arr, $struct);
	}

	static public function convertRegexp($regexp, $flags = '')
	{
		$pos = strrpos($regexp, $regexp[0]);

		$modifiers = substr($regexp, $pos + 1);
		$regexp    = substr($regexp, 1, $pos - 1);

		if (strpos($modifiers, 's') !== false)
		{
			/**
			* Uses the "s" modifier, which doesn't exist in Javascript RegExp and has
			* to be replaced with the character class [\s\S]
			*/
			$regexp = preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)\\.#', '$1[\\s\\S]', $regexp);
		}

		/**
		* Replace \pL with \w because Javascript doesn't support Unicode properties. Other Unicode
		* properties are currently unsupported.
		*/
		$regexp = str_replace('\\pL', '\\w', $regexp);

		$modifiers = preg_replace('#[SusD]#', '', $modifiers);

		$js = 'new RegExp(' . json_encode($regexp)
		    . (($flags || $modifiers) ?  ',' . json_encode($flags . $modifiers) : '')
		    . ')';

		return $js;
	}

	static public function encodeArray(array $arr, array $struct = array())
	{
		$match = array();

		foreach ($struct as $name => $keypaths)
		{
			foreach ($arr as $k => $v)
			{
				$match[$name][$k] =
					(in_array(array($k), $keypaths, true)
					|| in_array(array(true), $keypaths, true));
			}
		}

		foreach ($arr as $k => &$v)
		{
			if (!empty($match['isRegexp'][$k]))
			{
				$v = self::convertRegexp($v);
			}
			elseif (!empty($match['isGlobalRegexp'][$k]))
			{
				$v = self::convertRegexp($v, 'g');
			}
			elseif (is_array($v))
			{
				$v = self::encodeArray(
					$v,
					self::filterKeyPaths($struct, $k)
				);
			}
			else
			{
				$v = json_encode($v);
			}
		}
		unset($v);

		if (array_keys($arr) === range(0, count($arr) - 1))
		{
			return '[' . implode(',', $arr) . ']';
		}

		$ret = array();
		foreach ($arr as $k => $v)
		{
			if (!empty($match['preserveKeys'][$k])
			 || preg_match(self::RESERVED_WORDS_REGEXP, $k)
			 || !preg_match('#^[a-z_0-9]+$#Di', $k))
			{
				$k = json_encode($k);
			}

			$ret[] = "$k:" . $v;
		}

		return '{' . implode(',', $ret) . '}';
	}

	static protected function filterKeyPaths(array $struct, $key)
	{
		$ret = array();
		foreach ($struct as $name => $keypaths)
		{
			foreach ($keypaths as $keypath)
			{
				if ($keypath[0] === $key
				 || $keypath[0] === true)
				{
					$ret[$name][] = array_slice($keypath, 1);
				}
			}
		}

		return array_map('array_filter', $ret);
	}
}