See also [API changes](API_changes.md).


## 2.0.0


## 1.4.0

`$configurator->asConfig()` does not implicitly call `$configurator->plugins->finalize()` anymore. The latter remains available and can be called explicitly if necessary, before the configuration is generated.
