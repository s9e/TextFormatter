<h2>How it works</h2>

From posting to displaying to editing, s9e\\TextFormatter separates its operations in 4 distinct phases:

  * __Configuration__: configure s9e\\TextFormatter and the plugins you want to use, or use a [preconfigured bundle](Basic_usage/Configuration/Zero_config.md)

  * __Parsing__: the text is parsed and can be stored as an XML document

  * __Rendering__: the XML document is transformed into HTML

  * __Unparsing__: the XML document is transformed back into plain text

```
                Parsing 
              ↗         ↘
Original text             XML → Rendering → HTML
              ↖         ↙
               Unparsing
```

The configurator object can be used to generate a parser object and a renderer object. They can be serialized and should be cached for performance. Preconfigured parsers and renderers are available through bundles.

```
                             $parser
                           ↗    ↕
$configurator → finalize()   (cache)
                           ↘    ↕
                             $renderer
```

The parser object transforms plain text into XML, the renderer object transforms XML to HTML. The XML can be transformed back to the original text using the `s9e\TextFormatter\Unparser::unparse()` static method.

```
        $parser->parse()
      ↗                  ↘
$text                      $xml → $renderer->render() → $html
      ↖                  ↙
       Unparser::unparse()
```
