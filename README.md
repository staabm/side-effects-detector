Analyzes php-code for side-effects.

When code has no side-effects it can e.g. be used with `eval($code)` in the same process without interfering.

Example:

```php
use staabm\SideEffectsDetector\SideEffectsDetector;

$code = '<?php version_compare(PHP_VERSION, "8.0", ">=") or echo("skip because attributes are only available since PHP 8.0");';

$detector = new SideEffectsDetector();
var_dump($detector->hasSideEffects($code)); // true
```

In case you want treat output not to be a side-effect:

```php
use staabm\SideEffectsDetector\SideEffectsDetector;

$code = '<?php version_compare(PHP_VERSION, "8.0", ">=") or echo("skip because attributes are only available since PHP 8.0");';

$detector = new SideEffectsDetector();
var_dump($detector->hasSideEffects($code, $ignoreOutput=true)); // false
```

In case functions are called which are not known to have side-effects - e.g. userland functions - `null` is returned.

```php
use staabm\SideEffectsDetector\SideEffectsDetector;

$code = '<?php userlandFunction();';

$detector = new SideEffectsDetector();
var_dump($detector->hasSideEffects($code, $ignoreOutput=false)); // null
```
