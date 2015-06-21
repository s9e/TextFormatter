<h2>Restrict/allow schemes</h2>

By default, only http:// and https:// URLs are allowed. URLs that use a difference scheme are rejected but they might still appear as plain text.

In the following example, we remove http from the list of allowed schemes and add ftps.

```php
$configurator = new s9e\TextFormatter\Configurator;

$configurator->urlConfig->allowScheme('ftps');
$configurator->urlConfig->disallowScheme('http');

print_r($configurator->urlConfig->getAllowedSchemes());
```
```
Array
(
    [0] => https
    [1] => ftps
)
```
