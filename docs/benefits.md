* Plugin-based. BBCodes, censor, emoticons, HTML plugins, etc... Conflicts resolved coherently. Custom plugins can be added without worrying about interactions
* Intermediate representation in XML can be easily read, easily and losslessly transformed back to original form
* Templates in XSL, a Turing complete engine that resembles (X)HTML
* Strong validation. Uses ext/filter for basic types. Support for adding custom filters or overriding built-in filters. Unsafe templates are rejected
* Comprehensive set of rules available to control where and how tags can be used (via blacklist or whitelist.) Also controls whether newlines should be converted or whitespace trimmed. Ruleset based on HTML5 can be automatically generated
* BBCodes plugin accepts and extends the format used in phpBB's ACP
* Parser and Renderer have a minimalistic API and can be serialized for storage. Configurator has a rich API with sensible defaults
