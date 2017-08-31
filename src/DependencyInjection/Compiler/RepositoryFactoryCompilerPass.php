<?php

namespace Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class RepositoryFactoryCompilerPass
 *
 * @package Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler
 * @author  Nikita Loges
 * @company tenolo GbR
 */
class RepositoryFactoryCompilerPass implements CompilerPassInterface
{
    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        // custom factory
        $factory = $container->findDefinition('shapecode_raas.doctrine.repository_factory');

        // replace default repository factory
        $container->findDefinition('doctrine.orm.configuration')->addMethodCall('setRepositoryFactory', [$factory]);
    }

}
