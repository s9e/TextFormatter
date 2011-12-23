TODO
====

- add an option to enable/disable individual tags in Parser(?)
- add an option to toggle whether a tag can be used at the root of the document. (the alternative is to create as many requireParent rules as necessary)
- add an option to toggle whether a tag must be empty and/or can have an end tag
- finish the Fabric plugin
- create a plugin for the Markdown syntax (or possibly Upskirt/Redcarpet) and name it Downmark to feel witty and have a plugin for each letter from A to H
- write documentation, el oh el
- ConfigBuilder::buildRegexpFromList() -- find in which cases using lookahead assertion is beneficial and in which case it is not. Currently it can generate stuff like (?=[ab])[ab]
- Replace source manipulation in JSParserGenerator::removeDeadCode() with more hints