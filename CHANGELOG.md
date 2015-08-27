0.2.0 (2015-08-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/0.1.0...0.2.0)

New
---

 - 334cb3a Annotation
 - b7bcb10 Autoimage plugin
 - 4fd654d Duplicate check in ProgrammableCallback::addParameterByName() and fixed the signatures in default tag/attribute callbacks
 - 6fa7f32 Global flag to RegexpConvertor::toJS()
 - c70adbc JavaScript\ConfigOptimizer
 - aba3ed7 JavaScript\Encoder
 - 2534b4b JavaScript\Encoder tests
 - b897952 MergeConsecutiveCopyOf template normalization
 - a648a55 MergeConsecutiveCopyOf to the XSLT renderer's optimizations
 - 4775bc6 Minifier\Noop test
 - 38759de Missing assertion in HintGenerator tests
 - e749684 Missing files to the test suite
 - a6bb5d2 Regexp test
 - 84a7bab RegexpConvertor test
 - 78b31ce RegexpParser::getCaptureNames()

Changed
-------

 - 53276d4 Annotation
 - eac8ee9 AttributePreprocessor
 - beecacd AttributePreprocessor to map named captures in advance
 - 4604346 AttributePreprocessorCollection: fixed regexps incorrectly marked as global
 - b3f5568 AttributePreprocessorCollection: removed dead assignment
 - 4f27bb2 AttributePreprocessorCollection: return config arrays, not object instances
 - 05fccd5 AttributePreprocessorCollectionTest
 - 8fa5b53 Autolink: use a slightly worse priority
 - 0e7c2b6 BBCodeMonkey: reorganized code
 - 1b3606d BBCodes: upgraded highlight.js to 8.7
 - 7bf84af Bundles
 - 3ef90de Emoji: added test
 - 5e4438d Enabled the Quick renderer by default in the PHP renderer generator
 - 3c9d5ab Extern.js
 - 6582b5e Externs
 - 8cfb994 Fatdown
 - dcd1345 Fixtures for ClosureCompilerService tests
 - e5d33b5 Folded JavaScript\RegExp functionality into Items\Regexp and made it a variant
 - 4ab7e9c Improved JavaScript config deduplication
 - f699ada JavaScript: added HINT.namespaces
 - 419cc9f JavaScript: changed injectConfig() to work on a value, not by reference. No functional change
 - 259c032 JavaScript: moved callback generation to its own class
 - f2db84d JavaScript: moved hint generation to its own class
 - d8a3862 JavaScript: reorganized code. No functional change
 - d945b5b JavaScript: reorganized code. No functional change
 - be99eba JavaScript: reorganized hints initialization
 - 0797d19 JavaScript: replaced unnecessary instances of Code
 - 8a11d94 JavaScript: simplified tag hints generation
 - 5e20e7f JavaScript: wrapped the source in a function to protect the global scope
 - 1903f14 JavaScript\Encoder: encode empty array as JavaScript array
 - 43f1d9e JavaScript\Encoder: simplified array encoding
 - dff68db JavaScript\Encoder: sort object properties in lexical order
 - 02e56f5 Lazy-load the rendering engine
 - da9ed55 Litedown parser. No functional change
 - 745810d Litedown: changed the way link titles are parsed. Added support for balancing one level of parentheses inside of links
 - 0bf9254 Litedown: fixed ATX headers with no text. Fixes #14
 - 18ad9b1 Litedown: fixed incorrect length/offset when overwriting fenced code blocks
 - c018998 Litedown: protect against infinite loops
 - 6b7ed36 Litedown: removed a conditional that wasn't needed. No functional change
 - bed1030 Litedown: reorganized emphasis algorithm. No functional change intended
 - 97eab70 Litedown: reorganized emphasis handling. No functional change
 - 295f963 Litedown: replaced emphasis algorithm. No functional change intended
 - 1d16b72 Litedown: updated ATX-style headers to match the most common behaviour
 - 07a470f MediaEmbed: removed Blip
 - 3a8d55a MediaEmbed: removed ColbertNation and TheDailyShow
 - e150ad5 MediaEmbed: updated ColbertNation
 - 0a78b41 MediaEmbed: updated Gfycat
 - 69f65d7 MediaEmbed: updated Imgur
 - e0c7c50 MediaEmbed: updated KHL
 - f6f8044 Moved AttributePreprocessor::getAttributes() functionality to Regexp::getNamedCaptures()
 - 6d6fe2d Parser: added covering test
 - 7d496e0 Parser: added verbatim tags
 - 969f005 Preg: added covering test
 - 2ce64fe Preg: updated the PHP parser to make it more consistent with the JavaScript parser
 - c5363a5 ProgrammableCallback: automatically set the default JS callback to 'returnFalse'
 - df5530d Reenabled tests
 - 741ac94 Refactored the Escaper plugin
 - 9db26eb Regexp: added getCaptureNames()
 - 5a3cce6 RegexpConvertor: reorganized some code. No functional change
 - 6939637 RegexpConvertor: simplified the handling of unsupported tokens
 - f8d0993 Reordered keywords. No functional change intended
 - 451bc11 Set the default tag filters' JavaScript callback and simplified JavaScript callbacks generation
 - ec56784 Simplified AttributePreprocessorCollection::asConfig()
 - 3252191 Tag

Fixed
-----

 - 87b90aa AddVerbatim()
 - 5c78def Attribute preprocessors not reset between executions
 - 2d9b719 AttributePreprocessor to not be a global regexp
 - 217919a Filenames that have changed because of new externs
 - 0e8d2da PHP 5.3

********************************************************************************

0.1.0 (2015-08-12)
==================

Initial release
