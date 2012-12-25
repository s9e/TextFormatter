* Plugin-based. BBCodes, censor, emoticons, HTML plugins, etc... Conflicts resolved coherently. Custom plugins can be added without worrying about interactions
* Intermediate representation in XML can be easily read, and losslessly transformed back to original form. Solid, stable format that's forward-compatible with future plugins
* Templates in XSL, a Turing complete engine that resembles (X)HTML
* Strong validation. Uses ext/filter for basic types. Support for adding custom filters or overriding built-in filters. Unsafe templates are rejected
* Comprehensive set of rules available to control where and how tags can be used (via blacklist or whitelist.) Also controls whether newlines should be converted or whitespace trimmed. Ruleset based on HTML5 can be automatically generated
* Built-in limits to prevent abuse/DoS. Default values (can be set to an arbitrary number) - max 1000 regexp matches processed (limit per plugin, only plugins with a regexp phase though), max 100 tags used (limit per tag pair, e.g. 100 times [b][/b]), max 10 levels of the same tag nested (limit per tag)
* BBCodes plugin accepts and extends the format used in phpBB's ACP
* Parser and Renderer have a minimalistic API and can be serialized for storage. Configurator has a rich API with sensible defaults
