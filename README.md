Symfony - Repository as a Service (RaaS)
=======================

[![SensioLabsInsight](https://insight.sensiolabs.com/projects/45d25f2d-0f87-43f7-ba74-b239562c41e1/mini.png)](https://insight.sensiolabs.com/projects/45d25f2d-0f87-43f7-ba74-b239562c41e1)
[![Latest Stable Version](https://poser.pugx.org/shapecode/repository-as-a-service/v/stable)](https://packagist.org/packages/shapecode/repository-as-a-service)
[![Total Downloads](https://poser.pugx.org/shapecode/repository-as-a-service/downloads)](https://packagist.org/packages/shapecode/repository-as-a-service)
[![License](https://poser.pugx.org/shapecode/repository-as-a-service/license)](https://packagist.org/packages/shapecode/repository-as-a-service)

This bundle allows to register repositories as a service.

Install instructions
--------------------------------

Installing this bundle can be done through these simple steps:

Add the bundle to your project as a composer dependency:
```javascript
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
```sh
$ composer update
```

Add the bundle to your application kernel:
```php
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
```yaml
app.repository.example:
    class: %app.repository.example.class%
    tags:
        -  { name: doctrine.repository, class: %app.entity.example.class%, alias: example_repository }
```

or let the bundle do the job for you. It creates automatically services for you. Just access it with "lowercaseentitnyname_repository".
 
```php
<?php

$this->getContainer()->get('lowercaseentitnyname_repository');
```
 
The old way to get repository is also supported. If you get them like this ...

```php
<?php

$this->getRepository('ShapecodeRasSBundle:TestEntity');
```

... you get the service off the repository instead.
