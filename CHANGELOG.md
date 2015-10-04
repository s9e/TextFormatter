0.4.2 (2015-10-04)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/50c790c1bcb80d8ed5a5cb9b81c6a6fbb6a1d0f4...a8d84fb8a2d6150862531ed8622d6c1a64b1fb90)

### Added

 - `73e14ab` BBCodes: ensure that end tags added by lookahead are not duplicated
 - `fd6bd30` Emoji: added draggable="false" to Emoji One images
 - `d32e93d` MediaEmbed: added support for private tracks in SoundCloud
 - `0dd8a61` PHP renderer generator: added support for raw output

### Changed

 - `ce681ae` BBCodes: updated the default CODE definition
 - `627d41c` JavaScript\ConfigOptimizer: do not attempt to deduplicate simple variables
 - `7afa5f9` MediaEmbed: updated wget() to send a User-agent header
 - `31e3b26` TemplateParser: set the escape value of literal text in script elements to "raw"


0.4.1 (2015-09-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/15b0005595cc38c76355f376cc5576968db79de6...30ca6acb15a467777f6229bacf4158a8199a38cd)

### Fixed

 - `b6dd56b` MediaEmbed: fixed malformed XSL when an attribute value contains an angle bracket but is not XSL
 - `0d4675c` MediaEmbed: fixed responsive embeds alignment

### Changed

 - `30ca6ac` MediaEmbed: updated Medium
 - `7887f79` MediaEmbed: updated SoundCloud


0.4.0 (2015-09-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/5a50dab7662d4083129287bd1c895e2aaf884e9a...5863c4cb5df880d315188b17145410105ba71773)

### Added

 - `7abbe26` Added AVTHelper::toXSL()
 - `5863c4c` Added JavaScript\StylesheetCompressor
 - `257a413` MediaEmbed: added support for Google Drive
 - `a6f99bb` MediaEmbed: added support for multiple-choice templates
 - `82f06e3` TemplateNormalizations: added OptimizeChoose
 - `bc8e764` TemplateNormalizations: added additive identity optimization to FoldArithmeticConstants
 - `9548cee` TemplateNormalizations: added support for decimal values in InlineXPathLiterals
 - `0a6ff7a` TemplateNormalizations: added support for multiplications, divisions and sub expressions in FoldConstants
 - `7a439ff` XPathConvertor: added support for parenthesized math expressions

### Removed

 - `dc8a261` Emoji: removed unnecessary parentheses in JavaScript regexp
 - `edacfa8` MediaEmbed: removed ESPN Deportes
 - `e20df9f` MediaEmbed: removed enableResponsiveEmbeds() and disabledResponsiveEmbeds()
 - `4e7f087` MediaEmbed: removed the embed element from Flash templates
 - `b1500b6` PHP renderer generator: removed constant math evaluation which was made redundant by the FoldArithmeticConstants template normalization pass

### Fixed

 - `9c8eaa0` MediaEmbed: fixed the MEDIA tag filter to not create a tag if it does not match a known site
 - `a5feb09` Quick renderer: fixed a potential issue with string comparison against single quotes
 - `80f2c9a` Quick renderer: fixed incorrect comparison against literals that contain a single quote

### Changed

 - `e1809c3` BBCodes: updated Highlight.js in CODE BBCode
 - `c3f79fb` Censor: do not escape single quotes in Helper::reparse()
 - `050b051` MediaEmbed: overhauled responsive embeds
 - `2ac29f1` MediaEmbed: reorganized filterTag()
 - `5ff9c1e` MediaEmbed: reorganized template generation
 - `7ede2a7` MediaEmbed: updated Audiomack and SoundCloud
 - `1b8956f` MediaEmbed: updated Google Drive
 - `493ac00` MediaEmbed: updated IMDb
 - `455723e` MediaEmbed: updated Imgur
 - `4680bb5` MediaEmbed: updated Ustream
 - `5552810` Moved JavaScript callbacks deduplication to ConfigOptimizer
 - `2bc612c` TemplateNormalizations: improved parentheses removal in FoldArithmeticConstants
 - `361212f` TemplateNormalizations: preserve strings content in FoldArithmeticConstants
 - `679f0d3` TemplateNormalizations: renamed FoldConstants to FoldArithmeticConstants
 - `470c1da` Updated docblock
 - `0765165` Utils: updated serializeAttributes() to escape quotes in a manner consistent with the parser
 - `caf1dc2` XPathHelper: updated minify() to remove more space around the div operator
 - `38a9c89` XPathHelper: updated minify() to remove spaces after a div operator


0.3.2 (2015-09-06)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/c6fad331e64aeea041ce2da4c5f0e0521bc76afc...dd3fe8c881856bb6279302932912cc8ab957efd1)

### New

 - `dd3fe8c` Added CharacterClassBuilder helper

### Changed

 - `c3f08fb` JavaScript: use returnFalse and returnTrue callbacks as-is
 - `0f5051d` MediaEmbed: simplified the regexp that matches text links


0.3.1 (2015-09-04)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/24a4d8a4ac4aea751428e788ff94cf298646f342...f1ef4fce3431c5ddd59fb345a9e2a839331ea8ad)

### New

 - `51bce03` ClosureCompilerService: added read timeout with a default value of 10s
 - `348e1de` JavaScript: added returnTrue() in utils.js

### Changed

 - `957ada3` ClosureCompilerApplication: updated command line options for v20150901 and turned off warnings
 - `d105dbc` JavaScript: created different externs for ClosureCompilerApplication and ClosureCompilerService
 - `3cab461` MediaEmbed: moved template generation out of add()
 - `c497eb3` MediaEmbed: normalized the order of characters in regexps' character classes
 - `ed7aaaf` MediaEmbed: replaced the 'unresponsive' attribute in site definitions with a 'responsive' attribute
 - `95007fb` MediaEmbed: simplified the generation of Flash templates
 - `a75dd32` Refreshed bundles
 - `d4e81cb` Regexp: avoid adding non-capturing subpatterns to regexps generated by getNamedCaptures() where they are not needed
 - `cd1f119` Regexp: simplified getNamedCaptures()

### Fixed

 - `d363f8b` MediaEmbed: fixed responsive Flash objects
 - `f1ef4fc` Parser: fixed incorrect tag removal


0.3.0 (2015-09-02)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/eab904365c31bb87a016d2c0876e0c149faf93a3...ce1d116ccbc15868856cb2fd65dc9897afd38360)

### Changed

 - `ce1d116` MediaEmbed: moved template generation to its own class
 - `be478e7` MediaEmbed: moved the 'unresponsive' attribute into the iframe/flash definition
 - `3f5e499` MediaEmbed: removed support for custom templates
 - `15e8ece` MediaEmbed: updated Vine

### Fixed

 - `c089b4a` XPathHelper: fixed false negative in isExpressionNumeric()


0.2.1 (2015-08-30)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/90a396d1d63d4c8f69b96a2450a35573b8d676c6...042d1779e37c4f1720042845e1842a1e1d9b5383)

### New

 - `6c181a7` MediaEmbed: added Oddshot.tv
 - `48d03be` TemplateNormalizations: added FoldConstants pass

### Changed

 - `de996ae` Litedown: gave inline links a slightly better priority to give them precedence over BBCodes

### Fixed

 - `6a44f9f` Litedown: fixed incorrect indentation inside fenced code blocks


0.2.0 (2015-08-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/0.1.0...0.2.0)


0.1.0 (2015-08-12)
==================

Initial release
