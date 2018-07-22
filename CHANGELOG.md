1.2.1 (2018-07-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/7b909f51a8dbaff75e8a50038d8a8c3001a59da1...8af61dce9a28079036fcdc0af897280931c4919a)

### Added

 - `23906f738` MediaEmbed: added Streamja
 - `149e28792` MediaEmbed: added support for Instagram's IGTV
 - `8af61dce9` TemplateNormalizations: added EnforceHTMLOmittedEndTags

### Removed

 - `972a5a2fa` MediaEmbed: removed defunct site Blab
 - `9cc11283d` MediaEmbed: removed defunct site Viagame

### Fixed

 - `c0b22b293` ElementInspector: fixed incorrect results in closesParent()

### Changed

 - `fd66aa6f2` MediaEmbed: updated Amazon
 - `8d2e3c683` MediaEmbed: updated Amazon (.in)
 - `4ccf4531f` MediaEmbed: updated Brightcove
 - `0d813046d` MediaEmbed: updated Gamespot
 - `142e4f953` MediaEmbed: updated Kickstarter
 - `25db4ecb8` MediaEmbed: updated MSNBC
 - `e152afe5a` Parser: prevent tags from starting or ending in the middle of a UTF sequence


1.2.0 (2018-06-10)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](http://s9etextformatter.readthedocs.io/Internals/API_changes/#120) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/7565cbb8d4ddd7650b6bc52a74a16fc915dc8d89...7b909f51a8dbaff75e8a50038d8a8c3001a59da1)

### Changed

 - `70e7d0631` MediaEmbed: updated Youku
 - `7b909f51a` NodeLocator: simplified code
 - `8b647bafc` Split TemplateHelper into separate components
 - `ec48771b3` TemplateHelper: simplified some code


1.1.7 (2018-06-03)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/2e99b01043cf5ea2d126489281f50114faadc5be...a7a43dec54df0e2bdda790569761128b4e58b4c9)

### Added

 - `50be65408` MediaEmbed: added support for custom headers when scraping

### Changed

 - `a7a43dec5` MediaEmbed: updated NPR
 - `fcaa772d5` Reorganized PHP serializer code


1.1.6 (2018-05-28)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/4ae20a30fb8feeb4d267fd274c7421fac07862f5...d16774a0e30e6a35270ef5edde06c13dc38006eb)

### Fixed

 - `d16774a0e` TemplateParser: fixed an issue with empty elements being incorrectly removed from default case


1.1.5 (2018-05-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/22b9429ed9b208b96313ce35c410bd73995af599...d973a468f048f05c4df6010b9bda4905556888d7)

### Removed

 - `1052a19e0` BBCodes: removed useless code from repository

### Fixed

 - `a8174fafa` Fixed a potential issue with DOMNode::$childNodes returning empty text nodes
 - `cf1069519` Fixed a potential issue with whitespace passed to the PHP renderer generator's serializer
 - `21ef901d2` TemplateParser: fixed a potential issue with DOMNode::$childNodes

### Changed

 - `588c7e272` BBCodes: reorganized Repository internals
 - `2aabcb262` TemplateNormalizations: avoid creating empty text nodes
 - `cb2ddd1df` TemplateParser: improved compatibility with Libxml 2.7
 - `6e1ddead0` TemplateParser: replaced PHP conditionals with XPath predicates


1.1.4 (2018-05-20)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/e00b876f49f38328ee27eb7521ffe7f0323649fe...8d5ece9fef9225bf7ba44f7e86b84131ec61155a)

### Added

 - `65c1fedf6` MediaEmbed: added support for custom user-agent value in scrapes

### Changed

 - `5d97a666a` ElementInspector: updated with HTML 5.2 changes
 - `8d5ece9fe` HTMLComments: remove trailing dashes to conform to HTML 5.2
 - `a213d7403` MediaEmbed: updated GoFundMe
 - `2a1977596` MediaEmbed: updated Imgur
 - `fd5a06765` MediaEmbed: updated Team Coco
 - `76a71ed88` MediaEmbed: updated Twitter
 - `5a558c1da` MediaEmbed: updated VK
 - `9e6255edc` MediaEmbed: updated VK's dimensions


1.1.3 (2018-04-14)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/60fbdf7d6315e06143e0a1405c66b812d3a6d8a0...c50c7525bcbf72ec983badf31e53a542d90af234)

### Added

 - `692a5157f` TemplateHelper: added support for select attribute in passthrough replacements

### Fixed

 - `4096d3523` MediaEmbed: fixed legacy Imgur embeds

### Changed

 - `a6b9a107b` BBCodes\Configurator\Repository: improved error message on bad repository file
 - `c50c7525b` XmlFileDefinitionCollection: load XML files using native PHP streams


1.1.2 (2018-03-31)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/a907d8dab55639b426a8a531616203e99d1a8b27...eee99ce5110e319bee5f68c4313a8e0c292dac66)

### Fixed

 - `dce9241bb` FoldConstantXPathExpressions: fixed an issue with function calls without arguments being incorrectly replaced
 - `95ea73acb` XPathConvertor: fixed boolean conversion of parameters set to '0' in conditionals
 - `49330b7fd` XPathConvertor: fixed handling of negative numbers that start with a zero
 - `815cef8b5` XPathConvertor: fixed handling of numbers that start with a zero

### Changed

 - `1d8456c11` MediaEmbed: updated Liveleak
 - `eee99ce51` MediaEmbed: updated MLB
 - `8cfcbe30c` XPathConvertor: refactored exportXPath()
 - `6d69d0ee0` XPathConvertor: simplified matchXPathForExport()
 - `57c7a5d71` XPathConvertor: simplified translate()


1.1.1 (2018-03-02)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b2dd04095e975cd2009bff0f3f83520105be09de...d5cafb5e1aa56900d4152f7d7526fdb991e1232e)

### Added

 - `8e6388310` MediaEmbed: added support for "allow" attributes in iframes
 - `26cad57a6` XPathConvertor: added support for current()

### Removed

 - `d5cafb5e1` MediaEmbed: removed Oddshot

### Changed

 - `556b72ff8` MediaEmbed: updated Gfycat
 - `83b6bfa5d` MediaEmbed: updated Spotify
 - `aa5532afc` TemplateHelper: reorganized replaceTokens()
 - `dcef175d3` XPathConvertor: refactored the way XPath expressions are tokenized


1.1.0 (2018-02-15)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/7817e0b0fe8bec571f801a029bc932464f38309f...2b30e75237c8ec532f0c72e6cdb100401f0cec3f)

### Added

 - `ac38f54cf` MediaEmbed: added support for Telegram
 - `5f4d01827` TemplateParser: added support for disable-output-escaping="yes"

### Removed

 - `08cb4d52a` MediaEmbed: removed support for Imgur's /t/ URLs

### Changed

 - `86f0c7ad7` BBCodes: updated SPOILER BBCode to prevent it from submitting forms
 - `7a4633b98` FixUnescapedCurlyBracesInHtmlAttributes: escape the first left brace of a function declaration
 - `74c90041a` InlineTextElements: do not inline text with disable-output-escaping="yes"
 - `58b2a389f` MediaEmbed: embed Imgur links to static images
 - `87671bcb8` MediaEmbed: updated BBC News
 - `95ed3fc0d` MediaEmbed: updated CBS News
 - `2b30e7523` MediaEmbed: updated Vevo
 - `25559caf4` MediaEmbed: updated Vevo
 - `289b1e26e` Simplified Quick renderer generator
 - `472c568b7` TemplateParser: refactored and split into subcomponents
 - `8a323795f` TemplateParser: simplified node removal


1.0.1 (2018-01-17)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/307ac0e9c69fe71b280652fe40f9da03def9d4dc...1b7e7a387d83bb048e4ca309e9e2e314624ab8d8)

### Added

 - `b72c04884` MediaEmbed: added FOX Sports

### Fixed

 - `1b7e7a387` Parser: fixed an issue with malformed XML in places where text is ignored


1.0.0 (2018-01-04)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](http://s9etextformatter.readthedocs.io/Internals/API_changes/#100) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/15b61f63ad4e0694c28fd84f5d65fecf6f2d4842...e821032b862a5b75c602901be7fbdd2ef45e5a5b)

### Added

 - `7577e4584` Added caching HTTP client
 - `a107e470a` Emoji: added support for textual codepoint sequences

### Removed

 - `147c773ea` BBCodes: removed $predefinedAttributes
 - `06920f1fb` MediaEmbed: removed $captureURLs
 - `ffde0c243` MediaEmbed: removed $createIndividualBBCodes
 - `ac33fb0a2` MediaEmbed: removed Vidme
 - `24d5ae989` MediaEmbed: removed Zippyshare
 - `def39285e` MediaEmbed: removed appendTemplate()
 - `7e343bf2d` MediaEmbed: removed support for custom schemes
 - `5921a6ad1` MediaEmbed: removed unused code
 - `21a9fce71` Parser: removed implicit invalidation in tag filters
 - `1c136a634` Removed InvalidTemplateException and InvalidXslException classes
 - `80ec0e641` Removed support for attribute generators and the {RANDOM} token in BBCodes

### Changed

 - `0c3bcb2c2` ElementInspector: updated for HTML 5.2
 - `ff6da69aa` HTMLElements: updated configurator for HTML 5.2
 - `0bc92a880` HTMLElements: updated the list of URL attributes
 - `dace43e40` Litedown: restricted the characters allowed in link references' URLs
 - `8092ea038` MediaEmbed: refactored plugin
 - `6557a14ae` MediaEmbed: updated 8tracks
 - `112b7457c` MediaEmbed: updated Dailymotion and Twitch
 - `488a15907` MediaEmbed: updated Facebook
 - `9079e50c3` MediaEmbed: updated Flickr
 - `b7ee53bf5` MediaEmbed: updated Imgur
 - `c0f863da8` MediaEmbed: updated Reddit
 - `eacf0e0f8` MediaEmbed: updated Spotify
 - `a5647eb14` MediaEmbed: updated Vimeo
 - `3d5cfef45` Parser: reorganized filter processing
 - `3de8c6af8` RegexpConvertor: updated Unicode properties to latest version
 - `1987a0ba4` TemplateHelper: updated for HTML 5.2
 - `788152faf` Updated live preview algorithm


0.13.1 (2017-12-10)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/9c1e4cebcb61535d14423263553b6414b42f456c...492c1d8e7d97b43614dc258d9b701ce9c21dc296)

### Added

 - `8aef6ff75` Added RemoveLivePreviewAttributes template normalization to non-JavaScript renderer generators
 - `badcf2a35` Added live preview attribute data-s9e-livepreview-ignore-attrs
 - `fa352dc5a` MediaEmbed: added live preview hints to dynamically-resized embeds

### Fixed

 - `500d43a51` RegexpBuilder: fixed infinite recursion during remerge

### Changed

 - `69332cc50` JavaScript: updated Closure Compiler externs
 - `62ed3073f` MediaEmbed: keep all optional fields in cached definitions
 - `1e56d2a60` MediaEmbed: moved attribute creation out of add()
 - `805b1ae9a` MediaEmbed: normalize site definitions as they are created
 - `ee4b1bdd4` MediaEmbed: updated MSNBC
 - `6e5b6c405` Ruleset: refactored internals to be more scalable
 - `d3fde3969` TemplateInspector: split individual elements' inpection into ElementInspector
 - `2d242d8a7` Updated live preview code and JavaScript externs
 - `f6551bb4d` Updated live preview to return the last node modified
 - `a75aef0cb` Updated the live preview algorithm


0.13.0 (2017-11-27)
===================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#0130) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/d50adf87a92d5c793f5a06df7af089bdbaa749e4...459dddf5bc5f81f78564e3562231b6f01f2f2791)

### Added

 - `fa9e938a4` Added MinifyInlineCSS template normalization
 - `d471649ce` Added timestamp attribute filter

### Removed

 - `0ef891332` Censor: removed unused method

### Fixed

 - `feeeac930` HashmapFilter: fixed an issue where hash keys were not preserved during JavaScript minification
 - `803d3c5b9` RegexpConvertor: fixed the conversion of empty regexps

### Changed

 - `9ccb7597a` BuiltInFilters: reorganized filters into separate classes
 - `66b1d77be` BuiltInFilters: simplified regexp-based filters
 - `4f998c818` Logger: renamed get() to getLogs()
 - `d2df9701b` MediaEmbed: updated MSNBC
 - `47b00f1c8` MediaEmbed: updated YouTube
 - `017c8f77c` MediaEmbed: updated cached definitions


0.12.0 (2017-11-11)
===================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#0120) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/f8d9f670a609d147573117d2e33ffb7ee6844eb8...87dd6663f0f514822b258968441301b9be24f58e)

### Added

 - `cff256ec2` Added OptimizeChooseText template normalization
 - `f7b88b2d8` Added UninlineAttributes template normalization
 - `8dfdedf97` BBCodeMonkey: added support for short-form optional attributes
 - `e92549522` Litedown: added support for subscript
 - `8e751a5a1` MediaEmbed: added Orfium

### Removed

 - `193dec933` Emoji: removed set configuration and hardcoded default template
 - `4744c5950` OptimizeChoose: removed redundant method
 - `232bb4e03` PHP renderer: removed source from renderer instance
 - `433651b7b` Removed HostedMinifier and RemoteCache
 - `cf79c62f4` Renderer: removed $metaElementsRegexp
 - `d37d1912a` TemplateInspector: removed getDOM()

### Changed

 - `0c6bc3331` AVTHelper: preserve whitespace in toXSL()
 - `4ea55275c` BBCodeMonkey: reorganized some code
 - `761bc3e35` ClosureCompilerService: updated service's URL
 - `34ff96f14` Detect unexpected input that cannot be rendered by the Quick renderer
 - `1fc40d19d` Emoji: use EmojiOne 3.1's PNG assets
 - `e6f58045d` Litedown: do not require a blank line before lists
 - `be2618c1d` Litedown: refactored Superscript pass
 - `b6368c235` Litedown: refactored parser into separate passes
 - `9ebe42bea` Litedown: reorganized Emphasis parser
 - `c941be98a` Litedown: simplified configurator setup
 - `f4be8e77d` Litedown: updated emphasis syntax to check for whitespace
 - `92ba7928c` Litedown: updated superscript syntax
 - `8d8e3cde3` MediaEmbed: updated Google Sheets
 - `cb942255a` MediaEmbed: updated Mail.Ru
 - `cb0c4d6dc` MediaEmbed: updated YouTube
 - `283087b99` MediaEmbed: updated YouTube to remove deprecated option
 - `e06cc7079` Moved static code from dynamically generated PHP renderers into a separate class
 - `713cc2e29` OptimizeChoose: moved generic methods to an abstract class
 - `21f63889d` Preg: replaced detection of invalid regexps
 - `d5bfb22a4` Quick: bypass entity decoding/encoding when multiple XML attribute values are output in an HTML attribute
 - `50d1bf55e` Quick: simplified boilerplate code
 - `3755ed483` Refactored and simplified template normalization classes
 - `f1a382413` Replaced custom branch tables with native switch statements in PHP renderers
 - `844df511a` TemplateInspector: reorganized code for readability
 - `d184254b7` TemplateInspector: simplified bitfield analysis
 - `bdcfec4fc` TemplateNormalizer: increased the maximum number of iterations
 - `e20a5c8b0` Updated JavaScript parsers to use bracket notation for character access
 - `f6a3dfdb6` Updated PHP requirements to PHP 5.4.7


0.11.2 (2017-10-02)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/0fb5ad37aa380cba7d8e4ae795a8f4c24ede057c...166118701944908429ecd2d94bbe461f1981c931)

### Fixed

 - `b82ab12b4` Litedown: fixed a bug triggered by empty code blocks
 - `77e81cfba` Quick: fixed an issue with @* in conditionals
 - `ac2353ba6` XmlFileDefinitionCollection: fixed an issue with atypical but valid definitions

### Changed

 - `14c4846e4` MediaEmbed: identify [media] tag pairs as markup
 - `aeaa1c8f1` MediaEmbed: made ->MediaEmbed->captureURLs accessible
 - `744f5a436` MediaEmbed: updated NHL
 - `ee5e8e13b` MediaEmbed: updated Podbean
 - `91fa461a0` Preg: normalize custom tag names
 - `955c8e78c` RulesGenerator: improved code's readability
 - `ba76dd0fd` XmlFileDefinitionCollection: cast known config values to the appropriate type


0.11.1 (2017-09-12)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/ad2f29a3453d112f75f9603be6190c6f4434ea02...d84102711ee361c84a5b2cc5fe93e2e1828071f8)

### Added

 - `eebac1ad4` MediaEmbed: added support for parameters metadata in site config

### Removed

 - `8eb826344` MediaEmbed: removed default dimensions from definitions

### Changed

 - `d84102711` BBCodes: updated default [img] BBCode to accept dimensions
 - `9e917849f` MediaEmbed: updated Gfycat
 - `57bff33ca` MediaEmbed: updated Imgur
 - `7e7aa3b23` MediaEmbed: updated Vidme
 - `d75174d02` MediaEmbed: updated YouTube


0.11.0 (2017-08-04)
===================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#0110) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/6266e40850301ddc0e40808e7abe3e507a3ca347...ad7afe67b971cc76c43f5d6445cc06ca12ea2502)

### Added

 - `0239f7e06` Added AllowAll rules generator
 - `76c01d93b` MediaEmbed: added support for Amazon India
 - `afe63fc05` RulesGenerator: added BlockElementsCloseFormattingElements to default generators

### Removed

 - `d94ef970f` Configurator: removed addHTML5Rules() and integrated it in finalize()
 - `3e9a48fe1` Configurator: removed the finalizeParser and finalizeRenderer options from finalize()
 - `599a21548` Configurator: removed the optimizeConfig option from finalize()
 - `c20153268` Configurator: removed the returnParser and returnRenderer options from finalize()
 - `77f96dfe8` RulesGenerator: removed support for parentHTML option
 - `4d3370389` Ruleset: removed defaultChildRule() and defaultDescendantRule()
 - `65c910a20` TemplateInspector: removed isIframe()

### Fixed

 - `f5f176c8c` Utils: fixed removeTag() failing with arbitrarily high  values

### Changed

 - `4b05c5233` Autoimage: reversed tag priority to allow Autolink to linkify the image
 - `a4862b55e` Autovideo: explicitly allow URL tags to be used as fallback
 - `9970de5da` Internals: increased the default limits for tags and other resources
 - `c31a802d1` MediaEmbed: explicitly allow URL tags to be used as fallback
 - `2d3cc9ba9` MediaEmbed: limit the width of dynamically-sized embeds to 100%
 - `a2dba6ee0` MediaEmbed: reorganized template generation for readability
 - `a58a52a34` MediaEmbed: updated Amazon
 - `a7088bd48` MediaEmbed: updated Amazon
 - `4fc5fbda2` MediaEmbed: updated Getty
 - `2074eeabc` MediaEmbed: updated Google Drive
 - `6b26c91cd` MediaEmbed: updated Vidme
 - `608840ec2` MediaEmbed: use span as responsive wrapper
 - `15f65327a` Rules: the default rule for tags is now `deny`. `allowDescendant` and `denyDescendant` now only apply to non-child descendant


0.10.1 (2017-07-03)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/691c181e0313eccca750f90b9471cf7d1540fea7...98f1ec319bc6943fb3bb00cd1915398d3b34ac41)

### Fixed

 - `98f1ec319` Censor: fixed an issue where Helper::censorHtml() would not use the correct replacement


0.10.0 (2017-07-03)
===================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#0100) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/d8617a1858407057ff7edd368bcd550075ae903e...023d2f1a70bbfd8a72ce95d086c60c0eb6073908)

### Added

 - `438497a86` MediaEmbed: added support for new Twitch Clips URLs
 - `e12a63653` TemplateParser: added support for namespaces

### Removed

 - `f0a1b6589` Censor: removed Helper::reparse()
 - `023d2f1a7` Tag: removed setSortPriority()

### Fixed

 - `51fce5c9d` Censor: fixed an issue with special characters in censorHtml()

### Changed

 - `5390c3af6` BBCodes: updated default spoiler BBCode
 - `80e813320` MediaEmbed: updated Audiomack
 - `af3c2e758` MediaEmbed: updated audioBoom
 - `3e32c86eb` MediaEmbed: updated vidme


0.9.6 (2017-05-10)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/da98196db2b0490c62d4f5ba4ce559a0921e3200...7d7e16ebfaa3ecc59093970f2a3a6ed72a9c2f46)

### Added

 - `dc754f8d9` BBCodeMonkey: added support for setting nestingLimit and tagLimit in definitions
 - `c6edaf190` BuiltInFilters: added support for PHP 7.2
 - `739fa2ee9` HotnameList: added support for PHP 7.2
 - `bd9a5d697` XPathConvertor: added support for PHP 7.2

### Removed

 - `07abb3413` MediaEmbed: removed the authoritative part of SoundCloud URLs used as id

### Fixed

 - `ed780e3b9` FixUnescapedCurlyBracesInHtmlAttributes: fixed an issue where properly escaped braces would get incorrectly escaped again

### Changed

 - `7d7e16ebf` FixUnescapedCurlyBracesInHtmlAttributes: improved support for HTML attributes that end with an odd number of left braces
 - `76b806e92` HTMLElements: updated list of URL attributes
 - `6aba111ff` MediaEmbed: systematically scrape track_id from SoundCloud URLs
 - `935381522` MediaEmbed: updated BBC News
 - `d8f6c0573` MediaEmbed: updated Pinterest to exclude "explore" links


0.9.5 (2017-04-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b8210d933e4f10955de62d4252258736678cdbaa...e0de18cb68298028d56fd1a34e0f2b2bd3645d23)

### Added

 - `3159fe96a` XPathHelper: added support for ints and floats in export()

### Fixed

 - `284b4e08e` FoldArithmeticConstants: fixed an issue with number formatting in non-C locales
 - `65f5721d4` Parser: fixed an unbounded loop that can occur when a tag closes an ancestor with a worse priority

### Changed

 - `cd0a160b3` FoldArithmeticConstants: improved the parsing of decimal and negative numbers
 - `82abeb20c` Parser: do not apply closeAncestor/closeParent rules if maxFixingCost has been reached to mitigate against unbounded loops
 - `a8abcf6dd` Parser: tweaked regexp for performance
 - `b7ba9e117` XPathHelper: improved detection of numerical expressions


0.9.4 (2017-04-07)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/8175223a3c663c5dc9d8625a03a94cc1a4534e21...4ebc635ce9f57db7f3f586b1fac24721bbd307d5)

### Fixed

 - `a1b60abf9` TemplateInspector: fixed an issue where templates with a mixed content model would not allow any children

### Changed

 - `4ebc635ce` FixUnescapedCurlyBracesInHtmlAttributes: escape left braces that are not followed by a right brace
 - `3a3577129` MediaEmbed: updated Youku
 - `9e1197ae1` TemplateNormalizer: replaced the way iterations are counted


0.9.3 (2017-03-27)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/5e583aae68e5fe32faefe0a4efa474c0debeaaa8...ea80c9deb417008712ba93f9e68e9273a9c19966)

### Added

 - `10d67eec1` MediaEmbed: added livestreaming tag
 - `5e9de5cda` MediaEmbed: added support for ABC News embed URLs

### Fixed

 - `ea80c9deb` OptimizeChoose: fixed an issue where xsl:choose elements could be improperly repositioned

### Changed

 - `f44d2dede` MediaEmbed: accept uppercase schemes in IGN
 - `1356d05e8` MediaEmbed: accept uppercase schemes in SoundCloud
 - `231d1bc0e` MediaEmbed: updated Google Sheets
 - `1041b3e88` MediaEmbed: updated Oddshot
 - `d8ed8bf40` MediaEmbed: updated TinyPic
 - `b14e5abb4` MediaEmbed: updated Twitch


0.9.2 (2017-02-11)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/04b5ae0a14b3e4a5b1a85e7cfd1b2d0618861b84...d3f9ae4242e057ba5a9cae0b2b0c4e22db5cd67a)

### Added

 - `72091c2c4` Autoimage: added support for more punctuations
 - `4f9aa8bcd` Autovideo: added support for more punctuations
 - `18c63cea7` MediaEmbed: added support for attribution links from YouTube
 - `8bbb9b089` MediaEmbed: added support for new share links from YouTube
 - `4f5475ffe` TemplateInspector: added isIframe()

### Changed

 - `d3f9ae424` BBCodeMonkey: allow newlines in {TEXT} used in composite attributes
 - `e52087874` EnforceContentModels: limit content fallback to single iframes
 - `31f46d6cb` MediaEmbed: updated Twitch
 - `8d0d4a82a` MediaEmbed: updated vidme
 - `6d678d75b` TemplateInspector: elements with "display:none" won't be considered block elements


0.9.1 (2017-01-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/299ce48c0ebbfe05e0123fd55d5f2daa94026ddf...a8114878c43a0eb419f237bdb22bf773723dcc67)

### Added

 - `1f4c52189` BBCodeMonkey: added support for flexible whitespace in composite attributes
 - `22566a0a1` BBCodes: added TBODY and THEAD to the repository
 - `a8114878c` PHP renderer generators: added support for @* in conditions

### Changed

 - `f36db2024` BBCodeMonkey: TEXT and ANYTHING tokens don't have to consume any text in composite attributes
 - `3d7542c72` Parser: create tags generated by a fosterParent rule at the first available non-whitespace position
 - `b241fbd2d` Parser: remove empty tag pairs, including those with attributes
 - `0031b5fa0` Parser: trim whitespace when adding a magic end tag if the current tag ignores whitespace
 - `e8bcaed06` Parser: trim whitespace when closing multiple tags if any of them ignores whitespace


0.9.0 (2017-01-15)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#090) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/8fefdbf95ab97316172d0f49d83c9547d0998795...d8623b088dd5ab71250fa9d842c513628bca4a89)

### Added

 - `8a25af9fa` FixUnescapedCurlyBracesInHtmlAttributes: added more JavaScript-related replacements
 - `acdd16962` MediaEmbed: added support for Gifs.com
 - `42f549035` MediaEmbed: added support for Pinterest

### Removed

 - `25f2c231a` Removed the custom autoloader

### Fixed

 - `382b31df3` RegexpParser: fixed incorrect unanchored pattern
 - `ecf1d71e4` Utils: fixed raw newlines in replaceAttributes()

### Changed

 - `bd3e3edce` Autolink: simplified parentheses matching
 - `842b02716` JavaScript: do not replace the s9e object if it already exists
 - `19c33a310` JavaScript\ConfigOptimizer: do not deduplicate empty arrays and dictionaries
 - `caf68b1cf` MediaEmbed: updated Podbean
 - `9deb2f725` MediaEmbed: updated WSHH
 - `d8623b088` RegexpParser: anchored bracket matching pattern as a precaution
 - `f5f494fc7` TemplateForensics: renamed to TemplateInspector
 - `2f57d0383` TemplateInspector: replaced "self" type hints with "TemplateInspector"
 - `20c5ed80f` Utils: normalize control characters in replaceAttributes()


0.8.5 (2016-12-11)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b2e101e62b114684d2b096167590d330fc93a7c6...92f61dc74effb788028d5055e04c2dfa60d5bc62)

### Added

 - `e2910c4b1` FancyPants: added =/= as an alias for the not-equal sign
 - `a9a2f2fa2` FancyPants: added support for toggling groups of replacements
 - `d8644729c` FancyPants: added support for vulgar fractions
 - `5bc811858` PipeTables: added support for empty cells

### Changed

 - `ff0e5cf03` Emoji: updated aliases and changed JavaScript regexp to lowercase
 - `fcbe4f120` MediaEmbed: updated IMDb
 - `e7ad9f604` PipeTables: improved the priority of table tags
 - `9c4eff9ab` Updated composer.json with dev dependencies
 - `92f61dc74` XSLT: force HTML attributes to use double quotes


0.8.4 (2016-11-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/e3356dc6f68453ac814339a9cc1f301e13aa2c04...c5d2dbece918263151ba10d8566f4d3feeedfc01)

### Added

 - `29dd2ca78` Added #fontfamily attribute filter
 - `008025df4` Added PipeTables plugin
 - `d62d30a89` BBCodes: added align attribute to TD and TH
 - `f7da8cd9c` Fatdown: added PipeTables
 - `1b2a9b28f` MediaEmbed: added support for MLB.com
 - `ba6ce5e2d` MediaEmbed: added support for Twitch clips
 - `fffd95cde` MediaEmbed: added support for the new NHL video site

### Fixed

 - `3b3731fe6` FontfamilyFilter: fixed consecutive quoted strings

### Changed

 - `88bbafc3f` AttributeFilters: reorganized how each filter sets its own safeness
 - `f911748d3` BBCodes: disabled paragraphs inside [TD] and [TH]
 - `71c0fe828` BBCodes: updated default [font]
 - `8620a49e3` ClosureCompiler: updated externs
 - `85f6f41bb` HashmapFilter: reorganized code. No functional change
 - `5d2eea951` Litedown: improved the priority of QUOTE start tags
 - `ffde5aa2e` MediaEmbed: updated BBC News
 - `f4c750adc` MediaEmbed: updated Facebook
 - `06658f1b3` MediaEmbed: updated IMDb
 - `ce1b411d7` MediaEmbed: updated SoundCloud. No functional change
 - `507fcbddd` MediaEmbed: updated Spotify
 - `8a486df0d` Parser: addTagPair() now sets the priority of the end tag to minus the given value
 - `7db413b19` Parser: invalidate tags that are skipped
 - `c5d2dbece` RegexpFilter: reorganized code slightly
 - `f72332402` Ruleset: clear() should reset defaultChildRule and defaultDescendantRule rather than remove them


0.8.3 (2016-11-02)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/8bb0409ae0f5daf0edeecfbbe0fbe18060f628e2...83f2797599180ca4223264d3f003ed7d910f2a24)

### Added

 - `f83eb28` FancyPants: added support for not equal sign and guillemets

### Fixed

 - `6d5797c` Censor\Helper: fixed an issue with text in quotes being ignored
 - `83f2797` Quick renderer: fixed an issue where attribute values would not be saved

### Changed

 - `6dced78` FancyPants: cached tagName/attrName in a local variable for better JS minification
 - `7293db8` FancyPants: reorganized code. No functional change
 - `04a6539` FancyPants: simplified parseSymbolsAfterDigits()
 - `0300282` MediaEmbed: made the default [media] tag's name configurable


0.8.2 (2016-10-16)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/ea6e4e1fac53527769c19b81bed8aac94b59c562...de73726c19a13d17f48216ba013094b23b221a5f)

### Added

 - `4048a68` BBCodes: added [table], [tr], [th] and [td] to repository
 - `1e44987` Forum bundle: added [table], [tr], [th] and [td]
 - `90fcbce` MediaEmbed: added support for NBC News videos
 - `de73726` MediaEmbed: added support for Washington Post Video
 - `426e103` MediaEmbed: added support for new Pastebin URLs
 - `fd1094a` TemplateForensics: added support for more optional end tags

### Changed

 - `2805fba` Emoji: updated aliases
 - `915a281` Fatdown: disabled paragraphs inside of <td> and <th>
 - `3b8b047` FoldConstantXPathExpressions: replaced the blacklist of unsupported functions with a whitelist of supported functions


0.8.1 (2016-10-09)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/97125c831c206f6b0c597a5ed1c8df7a3510da6c...d26e62b0369ef7bfedb94a26708a1f1f87bbd050)

### Fixed

 - `e82316c` FoldConstantXPathExpressions: fixed detection of XPath node tests in uppercase

### Changed

 - `d26e62b` FoldConstantXPathExpressions: improved detection of nonfoldable expressions


0.8.0 (2016-10-09)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#080) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/7d08b19a0573b0b459a1206a0142da370bccda42...dc20ca832b5abbac7f24b65912b897b1a37f9197)

### Added

 - `f3ce808` FoldArithmeticConstants: added support for folding substractions
 - `83a3468` Litedown: added support for spaces in inline links' info
 - `7cb65e0` MediaEmbed: added 'name' and 'tags' metadata to CachedDefinitionCollection items
 - `4602feb` TemplateNormalizer: added FoldConstantXPathExpressions pass

### Removed

 - `8b2e036` Parser: removed manual garbage collection

### Changed

 - `dc20ca8` BBCodes: updated default [CODE]
 - `75b28f9` MediaEmbed: replaced the SiteDefinitionProvider API
 - `6d1d7c0` MediaEmbed: simplified the XML configuration reader


0.7.1 (2016-09-24)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/64c95e0c84183a46a9bffaa85bea546671b85b21...3c2ff8143055f7998d1cdab428157eaa67a44645)

### Added

 - `3c2ff81` Added Autovideo plugin
 - `8608895` Autoimage: added support for fallback link via Autolink
 - `ca3cc4e` MediaEmbed: added Steam store
 - `64bc419` MediaEmbed: added support for fallback link via Autolink
 - `2a7b9ff` MediaEmbed: added support for uppercase scheme

### Fixed

 - `0693ad7` BuiltInFilters.js: fixed unchecked access to undefined var
 - `dfa0e66` HTMLElements: fixed exception's message
 - `ba79789` TemplateForensics: fixed allowsChildElements() on a template with no xsl:apply-templates element
 - `8371057` Utils: fixed an issue with removeFormatting() where some tags were skipped

### Changed

 - `411f753` Autolink: excluded fullwidth and halfwidth punctuation from links
 - `02360fa` Emoji: simplified UTF-8 to codepoint algorithm
 - `a0a2546` Emoji: store hexadecimal codepoints padded to 4 characters
 - `31b88d0` Emoji: updated JavaScript regexp. No functional change
 - `b3b9e7b` EnforceContentModels: allow for some fallback content in templates with no xsl:apply-templates element
 - `f1fb0c3` HTMLElements: updated the list of URL attributes
 - `7f67345` MediaEmbed: updated Amazon
 - `9388645` RegexpConvertor: updated Unicode properties
 - `2023bfb` Replaced the default behaviour on duplicates in AttributeCollection, BBCodeCollection, EmoticonCollection and TagCollection
 - `e66f291` TemplateForensics: updated specs to HTML 5.1
 - `2c8bbf0` Utils: simplified the UTF-8 to codepoint algorithm


0.7.0 (2016-09-12)
==================

**⚠️ This release contains API changes. See [docs/Internals/API_changes.md](https://s9etextformatter.readthedocs.io/Internals/API_changes/#070) for a description. ⚠️**

[Full commit log](https://github.com/s9e/TextFormatter/compare/66605667be735e06ccbdf2b14c344c2b8bb84100...7cd1dfea62d9d35a40c206997e266918b9f1dfa1)

### Added

 - `7cd1dfe` Emoji: added support for UTR #51 Unicode Emoji, Version 3.0
 - `e1701fd` Forum bundle: added support for emoji
 - `646c85b` JavaScript: added support for urldecode() as a callback
 - `3626b16` MediaEmbed: added support for non-ASCII usernames in Google+

### Changed

 - `f654a16` BBCodes: replaced stripos() calls for performance
 - `3352264` Emoji: made EmojiOne the default image set
 - `aee1add` Emoji: updated path to Twemoji assets
 - `27d1b6b` MediaEmbed: improved support for embeds whose dimensions are fully dynamic
 - `76e4641` MediaEmbed: updated ESPN
 - `8a77784` MediaEmbed: updated Imgur
 - `ce36a47` MediaEmbed: updated TED Talks
 - `f3a0f52` MediaEmbed: updated VK
 - `2fc4807` Parser: improved the performance of out-of-order insertions in the tag stack
 - `4d3b0b0` Parser: simplified out-of-order insertion algorithm
 - `2507bfc` Parser: tweaked addTagPair() to reduce the need for additional sorting
 - `36c544e` Preg: set a higher priority to replacements over default plugins
 - `d0543dd` StylesheetCompressor: improved heuristics
 - `cda5b1f` Tag: setSortPriority() has been deprecated and will emit a warning


0.6.2 (2016-08-09)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/a2d2f3c33695ccabc1c5779abfd44736c592a6c2...6e32a09afa2a1676a81109b6fe31dd63db209e42)

### Added

 - `3d68336` Autoimage: added support for uppercased URLs
 - `c8253c9` BBCodeMonkey: added support for putting BBCode attributes in quotes


0.6.1 (2016-07-30)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/d841d6be7838c25b586a669285c3b03bc185c498...7f3031c419ed748181a123a8b01c6945f2ae821b)

### Fixed

 - `7f3031c` Litedown: fixed catastrophic backtracking when matching inline links/images


0.6.0 (2016-07-29)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/3e76dc3b0ce5acad0860eb3f77dada48f6cfffc1...e3c010a09ae5236ef145e5728b8cdd7665d9dbc2)

### Added

 - `5e6c18a` Litedown: added support for separating blockquotes with two blank lines
 - `636bd77` Litedown: added support for separating lists with two blank lines
 - `d77de85` MediaEmbed: added support for Plays.tv shortlinks

### Removed

 - `bf77ead` BuiltInFilters: removed public access to parseUrl()
 - `39a41bc` Configurator: removed getParser() and getRenderer()
 - `b005a40` Removed the Variant class and related API

### Fixed

 - `915226a` BuiltInFilters: fixed an issue with empty query/fragment in URLs

### Changed

 - `52e41b9` ClosureCompilerService: updated externs
 - `07317a1` Code: ensure that __toString() always returns a string
 - `e3c010a` Code: filterConfig('JS') should return the instance itself to preserve its content from being encoded as a string
 - `d4c9eca` Emoticons: customized exception message to be more meaningful
 - `584562c` Litedown: expanded escaping to include the left parenthesis and single quote
 - `a365537` Litedown: replaced the algorithm handling links and images
 - `ec2f000` Litedown: simplified ignoreEmphasis()
 - `0f533ba` MediaEmbed: updated Scribd
 - `af8ddab` MediaEmbed: updated Straw Poll
 - `78ca63b` MediaEmbed: updated YouTube
 - `2ded572` Moved JS parser loading to PluginBase::getBaseProperties()
 - `0f34747` PluginBase: getBaseProperties() should not return a 'js' element if there is no JS parser
 - `4ecb7f2` Quick renderer: replaced the export() algorithm
 - `fa99034` Regexp: replaced JavaScript-related API
 - `99fbcd1` RegexpConvertor: updated toJS() to return a string rather than an instance of Code


0.5.4 (2016-07-08)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/3122029dcaf12dc95ce584d43fa84ad92ad9619a...96a5a67eb3681b9f89d15dc6affd5896c3d877c6)

### Added

 - `d081f95` Added new rule type: createChild
 - `413fbeb` MediaEmbed: added support for Google Drive links that contain a domain name
 - `bfad6cc` MediaEmbed: added support for JW Platform

### Changed

 - `8a53870` Litedown: suspended escaping inside inline code spans
 - `afbbcd5` MediaEmbed: ignore the hash part of URLs when scraping
 - `2683928` MediaEmbed: updated Hudl
 - `0aff031` MediaEmbed: updated Hudl
 - `38ad33f` MediaEmbed: updated Reddit
 - `41967d0` MediaEmbed: updated SoundCloud
 - `d7526a4` MediaEmbed: updated XboxClips to exclude screenshots
 - `e141f0b` TemplateHelper: refactored some code and reformatted sources
 - `96a5a67` TemplateHelper: remove invalid attributes in loadTemplate()


0.5.3 (2016-06-14)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/4a7bda3e0ecd74b2d67106695ff9d292a0afa2ff...65d80c1ff686489dfc57a29a18bae1edfca9890a)

### Removed

 - `a55119c` Litedown: removed excessive slash stripping from attributes
 - `722763d` Removed unused variable from PHP renderers

### Fixed

 - `7ea7d29` Fixed an issue with newlines in attribute values in Quick rendering
 - `efd1ce3` Litedown: fixed an issue with quote markup inside of multiline attributes

### Changed

 - `65d80c1` Litedown: refactored inline code spans matching
 - `665444d` Parser: output LF characters as HTML entities in attribute values


0.5.2 (2016-06-06)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/702e6fdd6d7874ac25a721173c22435eb03fdc08...c66c9404a0d9ac29bc71d26df0f9c89d31679739)

### Fixed

 - `0c704d7` Litedown: fixed an issue with backticks inside inline code spans
 - `6e536e6` Litedown: fixed an issue with unbalanced inline code markers
 - `c66c940` TemplateHelper: fixed an issue with UTF-8 characters in HTML-to-XML conversion

### Changed

 - `bad5c74` BBCodes: updated Highlight.js to 9.4.0
 - `6e8c811` MediaEmbed: updated Getty Images


0.5.1 (2016-05-22)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b89e5b2db384a14a164db66871eac2ceab1e2832...253fbb59fbdbf0d664c93128b399fd157e955d7a)

### Added

 - `ff775b7` Emoji: added support for more aliases
 - `b573086` XPathConvertor: added support for less-than and greater-then comparisons

### Fixed

 - `558f34e` JavaScript\Encoder: fixed a potential issue with property names that start with a digit
 - `448e167` RegexpConvertor: fixed an issue with incorrect ranges used for \P properties

### Changed

 - `81106df` Emoji: updated EmojiOne's template to use lowercase filenames
 - `50e85d6` MediaEmbed: prevent division by zero in variable-sized embeds
 - `43acb03` MediaEmbed: updated Facebook
 - `3912aa9` MediaEmbed: updated Getty
 - `253fbb5` MediaEmbed: updated Internet Archive
 - `a579d85` RegexpConvertor: precompute the regexp that matches Unicode properties for performance
 - `e22a808` Updated Tinypic


0.5.0 (2016-04-28)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/efd5915ca5bf74f938fce40c8837d7fc781b642d...fe3dec1725bf870e4437b20e6be0d17652be87ea)

### Added

 - `64f3ee0` Added OL and UL to the Forum bundle
 - `794d664` BBCodes: added OL and UL to the default repository
 - `794127a` MediaEmbed: added NBC Sports
 - `8c6aa1e` MediaEmbed: added The Guardian
 - `7714ae9` MediaEmbed: added Veoh
 - `630ad0a` MediaEmbed: added a background image to YouTube and Twitter

### Removed

 - `c2a3a1c` Litedown: removed support for unquoted titles in links and images

### Fixed

 - `a13416c` Fatdown: fixed missing title in links

### Changed

 - `c032937` Autolink: made ->Autolink->matchWww public
 - `11633f8` Autolink: remove most non-letters at the end of the URL
 - `2a7de09` Autolink: replaced manual check with word boundary assertion
 - `90eaf86` BBCodes: updated Highlight.js to 9.3.0
 - `1608c79` Litedown: made the decodeHtmlEntities property public
 - `94f07bd` MediaEmbed: replaced all HTTP URLs with protocol-relative URLs in src attributes
 - `8b0a31d` MediaEmbed: updated Brightcove
 - `76638c6` MediaEmbed: updated Gfycat
 - `16038df` MediaEmbed: updated Gist
 - `ab22f0b` MediaEmbed: updated VBOX7
 - `b0b717a` MediaEmbed: updated Youku
 - `d9f1608` MediaEmbed: updated dumpert
 - `e49d6c9` OnlineMinifier: instantiate the HTTP client in the constructor
 - `15e3223` OnlineMinifier: renamed HTTP client property
 - `4f10e67` OptimizeChoose: streamlined conditional
 - `c6fd225` TemplateForensics: inspect an element's style to determine whether it's a block-level element


0.4.12 (2016-03-20)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/ac3fc397cfbbf1083da75c9df5fcc3eac38fbbfb...acb855967860a9c095cd338f7383c247f88aa928)

### Added

 - `266ccf8` Added support for named parameter 'text' in tag filters
 - `e5d1b85` MediaEmbed: added Brightcove
 - `5c5206c` MediaEmbed: added Healthguru
 - `34a49c1` MediaEmbed: added MRCTV
 - `04d14f1` MediaEmbed: added Video Detective
 - `ae8cc70` MediaEmbed: added support for FORA.tv
 - `9a741c3` MediaEmbed: added support for Livestream short links and old site
 - `bdc62e8` TemplateNormalizations: added SetRelNoreferrerOnTargetedLinks (enabled by default)

### Removed

 - `3b0682f` Litedown: removed support for space in inline links markup

### Fixed

 - `71064bb` Litedown: fixed an issue with Setext headers causing the next line to be ignored

### Changed

 - `f038f28` Litedown: prefix the class name used to identify the language of a code block
 - `7606ab6` Litedown: require code fences' length to match
 - `d2ff237` MediaEmbed: updated Dailymotion
 - `55ca396` MediaEmbed: updated NYTimes


0.4.11 (2016-02-21)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/b455eb19d389a67f576ab4cb8e9e25aae0609cb3...ec154ed850e8665afd1df50dc2a11bb78247e178)

### Added

 - `eb234d7` MediaEmbed: added support for LiveCap

### Fixed

 - `7e8fb2a` OptimizeChoose: fixed an issue when removing nested conditionals


0.4.10 (2016-02-11)
===================

[Full commit log](https://github.com/s9e/TextFormatter/compare/98836fa362a0ba24a8a3ff6be1db484067d8d8f2...0eeecb35d9934a454250e367b9eb3e5916494b88)

### Fixed

 - `9ca5270` Parser: fixed an issue with fosterParent and high-priority tags
 - `0eeecb3` Parser: fixed an issue with fosterParent and low-priority tags

### Changed

 - `11bdbe7` BlockElementsFosterFormattingElements: do not create a fosterParent rule if the template is not passthrough
 - `5bb3b77` Parser: adjusted the cost of fixing tags in fosterParent


0.4.9 (2016-02-10)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/350bb8c63660d888e62796567a3b6479f34a68dc...7c279935ae7e3360776dca6c5f512fa320433f6c)

### Added

 - `8a13fb4` RegexpBuilder: added support for non-Unicode strings

### Removed

 - `7bafcde` MediaEmbed: removed support for discontinued Mixcloud short links

### Fixed

 - `9f2b080` Litedown: fixed quote markup interpreted inside of fenced code blocks
 - `e56f871` OptimizeChoose: fixed an issue with node iteration over a live tree

### Changed

 - `09bc408` Http: prefer native stream to cURL if safe mode is on (PHP 5.3 only)
 - `9398ee9` MediaEmbed: updated ComedyCentral
 - `93a6a9b` MediaEmbed: updated Imgur
 - `5de2c70` MediaEmbed: updated Straw Poll
 - `bbabbb9` MediaEmbed: updated Tumblr
 - `5ac0294` Updated externs files for Closure Compiler v20160208


0.4.8 (2016-01-15)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/a7a66e4afae0ccebe729e8ea4b4b60c48bb41dfc...1050f78b5b2ebc4fe09a17b21d49d2d100a4f0ca)

### Added

 - `e98b8a0` Added an HTTP helper with support for cURL and native streams
 - `19ecb28` Added support for customisable timeout in HTTP clients
 - `88562c2` Added support for toggling SSL peer verification in HTTP clients
 - `c4e0a99` ClosureCompilerService: added support for configurable HTTP client
 - `ff15e3a` HostedMinifier: added support for configurable HTTP client
 - `8509ded` MediaEmbed: added Blab
 - `5feeae6` MediaEmbed: added support for HTTP client used for scraping
 - `e2cae50` RemoteCache: added support for configurable HTTP client

### Changed

 - `39139c3` ClosureCompilerService: reorganized code
 - `0c2b5aa` Curl: reset the request body when doing POST requests
 - `48d08f6` MediaEmbed: moved all GitHub iframes to RawGit
 - `c73392e` MediaEmbed: moved hosted iframes back to GitHub
 - `4e55063` MediaEmbed: updated Indiegogo
 - `6444043` Moved Http helper to the Utils namespace
 - `9973077` OnlineMinifier: automatically set timeout in getHttpClient()
 - `ab2c9f1` RemoteCache: updated for new API
 - `b2481cd` Reorganized native HTTP client's code
 - `0489bb8` Set headers unconditionally in native HTTP client


0.4.7 (2016-01-08)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/2b837825cac2da9ddf06867038c19096ffacc4dd...932f6f16738fad1d32e44a96a37347a0aa12635e)

### Added

 - `fd08fc1` Added support for MatthiasMullie\Minify
 - `8eda0fb` JavaScript: added support for converting \x{....} in regexps
 - `932f6f1` Minifiers: added experimental minifiers HostedMinifier and RemoteCache

### Removed

 - `e06bc52` MediaEmbed: removed Rdio

### Changed

 - `2c76bb3` BBCodes: updated Highlight.js to 9.0.0
 - `4be9a3f` ClosureCompilerApplication: cache the binary's hash for performance
 - `b730287` ClosureCompilerApplication: made constructor argument optional
 - `43b9f74` Minifier: use a default constant used as cache differentiator


0.4.6 (2015-12-21)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/4a6c4967cc506397d08053213dc1075d5bca9ec8...4dab11faae6382607c3b12612b274261ba110cc8)

### Added

 - `8e4706d` Added support for negative offsets in NormalizedList
 - `faeafd6` JavaScript: added FirstAvailable minifier
 - `1ec2d62` MediaEmbed: added Plays.tv
 - `853e63b` MediaEmbed: added support for timestamps in Twitch videos

### Fixed

 - `4dab11f` Litedown: fixed incorrect indentation in fenced code blocks

### Changed

 - `8bc9340` Litedown: trim whitespace around the language name in fenced code blocks
 - `19ee41c` Litedown: updated inline code syntax to bring it closer to Markdown's
 - `1f6e375` MediaEmbed: updated Pastebin
 - `299996e` MediaEmbed: updated Twitch to use their new player


0.4.5 (2015-12-04)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/c60a76996bd97733b2fe8ca14289237e43ce94f9...eb8ca6fc21770129b9244f6c544111f64f1a47f8)

### Added

 - `c40350b` Censor: added JavaScript hint
 - `650adb0` Censor: added JavaScript hint
 - `68fe6d6` Emoji: added JavaScript hint
 - `491042c` Emoji: added JavaScript hint
 - `23e84ac` Emoticons: added JavaScript hint
 - `7ec97e6` HTMLElements: added JavaScript hint
 - `5b5a6c4` JavaScript: added support for custom hints set by plugins
 - `fe484da` Litedown: added JavaScript hint for skipping HTML entity decoding
 - `0971ef0` Litedown: added support for decoding HTML entities in attribute values
 - `993f5cc` Preg: added JavaScript hint

### Removed

 - `3484b60` BBCodes: removed duplicate condition

### Fixed

 - `d0b6396` BBCodes: fixed improper pairing during parsing
 - `080d7aa` HTMLElements: fixed detection of empty elements

### Changed

 - `58dd013` HTMLEntities: ignore control characters encoded as HTML entities
 - `ffcdd48` MediaEmbed: replaced protocol-relative iframe URLs from GitHub to use HTTPS
 - `95b72d7` MediaEmbed: updated CNN
 - `ec41c55` MediaEmbed: updated GameTrailers
 - `2209d3c` Updated emoji script
 - `4ce201a` utils.js: cache the element used in html_entity_decode()


0.4.4 (2015-11-15)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/1a6cb0f14f0e368c973976afb7d0ac015c635d80...63dae49a256a8f3f263809c1a3e1f29e2b86a994)

### Added

 - `b6d4c3e` BBCodes: added support for lists that start at an arbitrary number
 - `72d8236` Litedown: added support for empty links
 - `c466adf` Litedown: added support for ordered lists that start at an arbitrary number
 - `54b40b8` Litedown: added support for reference links and images
 - `d26f829` Litedown: added support for single quoted and unquoted titles in links and images
 - `22426da` Litedown: added support for unescaped brackets in link text and image alt text
 - `76c7f6a` MediaEmbed: added support for country-specific Facebook links
 - `58d8541` XPathConvertor: added support for conditions with more than 3 boolean operations

### Fixed

 - `d1ce37e` MediaEmbed: fixed CSS overflow on iOS Safari
 - `10cfa29` XPathConvertor: fixed comparison to 0

### Changed

 - `845279a` Escaper: reverted to using a custom tag
 - `a94a625` MediaEmbed: updated Facebook
 - `13c9015` MediaEmbed: updated Facebook
 - `c742fa7` MediaEmbed: updated Imgur
 - `d7edfed` MediaEmbed: updated Imgur
 - `ff60c99` MediaEmbed: updated Instagram
 - `4e138d7` MediaEmbed: updated Xbox DVR
 - `048e433` MediaEmbed: updated vidme
 - `bb56400` Parser: automatically correct the length of ignore tags
 - `0676a8d` Updated Fatdown


0.4.3 (2015-10-29)
==================

[Full commit log](https://github.com/s9e/TextFormatter/compare/4ac91c44d353e8fb1b2ce5639a9ee71d92bad27d...d7547abecab3ac9d1b91f5e4c5e15e04883299f1)

### Added

 - `3abcb7d` Bundles: added getCachedParser() and getCachedRenderer()

### Removed

 - `5925a64` MediaEmbed: removed redundant code

### Changed

 - `b0ab098` Emoji: simplified template generation
 - `f624086` Litedown: allow lists to start immediately after a header or horizontal rule
 - `a871f19` Litedown: replaced the way block boundaries are set
 - `3c8c61d` MediaEmbed: updated Comedy Central
 - `ce982d7` MediaEmbed: updated Imgur
 - `df9e944` MediaEmbed: updated Imgur to not transform links to static images
 - `e5c72df` MediaEmbed: updated NPR
 - `cd24826` NormalizedCollection: save items in the lexical order of their keys


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
