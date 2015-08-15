###### August 2015

- Refactored the JavaScript generator [[1](https://github.com/s9e/TextFormatter/commit/aba3ed71b2536a7e8e5df7b3e8272e45adb08fff)] [[2](https://github.com/s9e/TextFormatter/commit/259c03233337378365d02ef6eec383edcbade6a8)] [[3](https://github.com/s9e/TextFormatter/commit/f2db84d51c471f6fd6247c2fb3138216ac3c51c4)]
- [Reduced the size of the JavaScript config through deduplication](https://github.com/s9e/TextFormatter/commit/c70adbc182a2a6291b1ca0d01dff612f570b54ab)
- Reduced the size of some XSLT templates [[1](https://github.com/s9e/TextFormatter/commit/b897952c6976ecc741ee1d56238cf2d03e9e3e3f)] [[2](https://github.com/s9e/TextFormatter/commit/a648a552c8f0677f707820c2111acbd1b6dc9746)]
- [Improved Litedown's inline links](https://github.com/s9e/TextFormatter/commit/745810d187be81ec4601e8fa8cf9ae3cf7e53d02)
- [Added the Autoimage plugin](https://github.com/s9e/TextFormatter/commit/b7bcb10574b6cc258a13e05e7c887391a54ca7bc)

###### July 2015

- Fixed two memory leaks that could happen on some versions of PHP [[1](https://github.com/s9e/TextFormatter/commit/0ca302b992697b2f85da85d323588884c50cdbb1)] [[2](https://github.com/s9e/TextFormatter/commit/844420e217a0dbd01341e3f027048f3962ccfd12)]
- [Replaced the data structure used in tags config to hold the list of allowed tags during parsing](https://github.com/s9e/TextFormatter/commit/ea7d8a9431271599ac580fbe0d5b1f8c314f0166)
- [New](https://github.com/s9e/TextFormatter/commit/99895aecfd04f0260d086cbca83d536aab57f61e) and [improved](https://github.com/s9e/TextFormatter/commit/bd5d6e4da151dd0d3e4f6244684b9cdc95dd4a39) Emoji plugin
- [Added FixUnescapedCurlyBracesInHtmlAttributes template normalization](https://github.com/s9e/TextFormatter/commit/f809b2c6eb9abc7b0d777e75fd7fe1228a11031f)

###### June 2015

- [Replaced the Generic plugin with a refactored Preg plugin](https://github.com/s9e/TextFormatter/commit/fe6b168cf031e5ce2bf0703a641e3e3f4c78dab5)
- Reduced the number of files in the release branches by merging together most of the files used by the configurator

###### May 2015

- [Removed PHP7 from allowed failures](https://github.com/s9e/TextFormatter/commit/94397e26178ce2b09f9f1562dd4ff722cf9ecc43)
- [Improved XPathConvertor to support more string functions natively](https://github.com/s9e/TextFormatter/commit/75a538a7503144d50ef45d697da144e6ae8e52c6)
- [Enabled the BlockElementsFosterFormattingElements rules generator by default](https://github.com/s9e/TextFormatter/commit/2cc65eeb9d995e9114526c74c987a71dbe4f72a8)

###### April 2015

- [MediaEmbed: added support for responsive embeds](https://github.com/s9e/TextFormatter/e2e4d3ebfcf7a5e067f0975c6af7c5be4c0515b4)
- [Added support for automatically encoding/decoding Unicode characters outside the BMP](https://github.com/s9e/TextFormatter/commit/42d614c08e5f3ce5d35a29867d58a7f50fed7c91)

###### March 2015

- [Added support for a custom regexp and limit in custom registered parsers](https://github.com/s9e/TextFormatter/03376d66118ee9c6ce22cf77aaa8db016ba31133)

###### February 2015

- [Autolink: added support for linking strings that start with "www."](https://github.com/s9e/TextFormatter/commit/52bb7babc45c2359bcea9bd25a94ecefc2d77bb9)
- [Added support for setting default attribute filters using a string such as "#int".](https://github.com/s9e/TextFormatter/commit/c184543565381695191b5f4fccc187a75f454f99)
- [Removed XHTML output mode.](https://github.com/s9e/TextFormatter/commit/6f3d6c7b15f4cc225b843db86700d2bcaace5044)

###### November 2014

- Improved XPathConvertor to support more math expressions natively. [[1](https://github.com/s9e/TextFormatter/commit/d3d4014d7a1b10b1b1fa48a9eda62666370a391f)] [[2](https://github.com/s9e/TextFormatter/commit/c1237dea4f5d67cbacdb56650f3cec7b78c8f7be)]

###### October 2014

- [Enabled all tests and features on HHVM](https://github.com/s9e/TextFormatter/commit/e81476633b6686255cff549843295376925ed093)

###### September 2014

- Added Emoji plugin.
- [Added BranchOutputOptimizer.](https://github.com/s9e/TextFormatter/commit/9cc2ceb5e2b0ce579b71aa02e62afc5f9278cb96)

###### August 2014

- Reworked the algorithm and rules defining automatic and manual line breaks. Enables forced line breaks in Litedown.

###### July 2014

- Implemented a fast PHP renderer dubbed the Quick renderer. Can be enabled with `$configurator->rendering->setEngine('PHP')->enableQuickRenderer = true`
- Cleaned up some internal helpers: TemplateParser, XPathParser, PHP renderer generator.

###### February 2014

- Added [Fatdown](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/10_Bundles/Fatdown.md), a Markdown-like bundle that doesn't suck.
- The default #url filter now accepts relative URLs.
