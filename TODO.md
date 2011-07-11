TODO
====

- add an option to enable/disable individual tags in Parser(?)
- add an option to toggle whether a tag can be used at the root of the document. (the alternative is to create as many requireParent rules as necessary)
- add an option to toggle whether a tag must be empty and/or can have an end tag
- finish the Fabric plugin
- create a plugin for the Markdown syntax (or possibly Upskirt/Redcarpet) and name it Downmark to feel witty and have a plugin for each letter from A to H
- create a plugin for filtered HTML
- create a plugin for raw HTML
- write documentation, el oh el
- ConfigBuilder::buildRegexpFromList() -- find in which cases using lookahead assertion is beneficial and in which case it is not
- add an option to toggle whether current tag's ancestors can be closed by current tag's descendants, e.g. [b][i][/b][/i]
- look into allowing URLs with no scheme and relative URLs, e.g. "/foo/bar", "//example.com/foo/bar" and "../foo/bar"
