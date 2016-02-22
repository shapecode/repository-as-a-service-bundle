Symfony - Repository as a Service (RaaS)
=======================

This bundle allows to register repositories as a service.

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
        "shapecode/repository-as-a-service": "~1.2"
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

or let the bundle do the job for you. It creates automatically services for you. Just access it with "lowercaseentitnyname_repository".
 
```
#!php
<?php

$this->getContainer()->get('lowercaseentitnyname_repository');
```
 
The old way to get repository is also supported. If you get them like this ...

```
#!php
<?php

$this->getRepository('ShapecodeRasSBundle:TestEntity');
```

... you get the service off the repository instead.
