How programmable callbacks get hardcoded in Javascript
------------------------------------------------------

A programmable callable (`s9e\TextFormatter\Configurator\Items\ProgrammableCallback`) is composed of a PHP callback, the callback's signature, an optional set of vars and an optional Javascript representation, which should take the form of one single function definition.

First, we need some javascript to replace the PHP callback. If the callback has a Javascript representation, we use that. Otherwise, if the callback is to a method from Parser, we use that method. Same if the callback is to a method from BuiltInFilters. Otherwise, if it looks like a PHP function, we look into `src/Configurator/Javascript/functions` for a `.js` file of the same name, e.g. `strtolower.js`. Finally, if the callback is none of those, we create a Javascript function that takes no arguments and returns false.

Now we have a callback, which is either the name of a function such `BuiltInFilters.filterNumber` or a literal function such as `function(str){return str.toLowerCase();}`. To avoid duplicating the callback's code, we hash the content of the callback, create functions that wrap around the callback code and use the functions' names as callbacks. For instance, the two previous callbacks would become

```
function cB86AE215(){return (function(str){return str.toLowerCase();})
```
generator `(function(attrName){})(attrName)`
tag filter `(function(tag, tagConfig){})(tag, tagConfig)`
attribute filter `(function(attrName, attrValue){})(attrName, attrValue)`


Now that we have a chunk of Javascript, how do we call it? We use the same signature as its PHP equivalent.