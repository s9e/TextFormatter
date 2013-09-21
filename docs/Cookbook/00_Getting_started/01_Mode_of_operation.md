## Mode of operation

From posting to displaying to editing, s9e\\TextFormatter separates its operations in 4 distinct phases:

  * __Configuration__: configure s9e\\TextFormatter and the plugins you want to use, or use a [preconfigured bundle](https://github.com/s9e/TextFormatter/blob/master/docs/Cookbook/Getting_started/Basic_usage/00_ZeroConfig.md)

  * __Parsing__: the text is parsed and can be stored as an XML document

  * __Rendering__: the XML document is transformed into HTML

  * __Unparsing__: the XML document is transformed back into plain text
