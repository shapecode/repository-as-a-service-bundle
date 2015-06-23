Symfony - Repository as a Service (RaaS)
=======================

This bundle allows to register repositories as a service.

Tested only against Symfony 2.7.

Install instructions
--------------------------------

Installing this bundle can be done through these simple steps:

Add the bundle to your project as a composer dependency:
```
#!javascript
// composer.json
{
    // ...
    require: {
        // ...
        "shapecode/repository-as-a-service": "~1.0"
    }
}
```

Update your composer installation:
```
#!bash
$ composer update
```

Add the bundle to your application kernel:
```
#!php
<?php

// application/ApplicationKernel.php
public function registerBundles()
{
	// ...
	$bundle = array(
		// ...
        new Shapecode\Bundle\RasSBundle\ShapecodeRasSBundle(),
	);
    // ...

    return $bundles;
}
```

Start using the bundle and set repositories as services:
```
#!yaml
app.repository.example:
    class: %app.repository.example.class%
    tags:
        -  { name: doctrine.repository, class: %app.entity.example.class%, alias: example_repository }
```

Ready

Update instructions
---------------------------

Do a [composer](https://getcomposer.org/doc/00-intro.md) update.

```
#!bash
$ composer update
```