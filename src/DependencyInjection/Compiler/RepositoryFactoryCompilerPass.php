<?php

namespace Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class RepositoryFactoryCompilerPass
 *
 * @package Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler
 * @author  Nikita Loges
 */
class RepositoryFactoryCompilerPass implements CompilerPassInterface
{

    const DOCTRINE_FACTORY_SERVICE_NAME = 'doctrine.orm.container_repository_factory';
    const SC_FACTORY_SERVICE_NAME = 'shapecode_raas.doctrine.repository_factory';
    const SC_FACTORY_SERVICE_INNER_NAME = 'shapecode_raas.doctrine.repository_factory.inner';

    /**
     * @inheritDoc
     */
    public function process(ContainerBuilder $container)
    {
        // custom factory
        $factory = $container->findDefinition(self::SC_FACTORY_SERVICE_NAME);

        /*
         * this feature is since doctrine bundle 1.8 default
         * we have to replace it
         * @since 1.8
         */
        if ($container->has(self::DOCTRINE_FACTORY_SERVICE_NAME)) {

            $factory->setDecoratedService(self::DOCTRINE_FACTORY_SERVICE_NAME);
            $factory->replaceArgument(1, new Reference(self::SC_FACTORY_SERVICE_INNER_NAME));

        } // @before 1.8
        else {

            // replace default repository factory
            $container->findDefinition('doctrine.orm.configuration')->addMethodCall('setRepositoryFactory', [$factory]);
        }

    }

}
