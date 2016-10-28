<?php

namespace Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DeclaredRepositoryServiceCompilerPass
 * @package Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler
 * @author Nikita Loges
 * @date 01.04.2015
 */
class DeclaredRepositoryServiceCompilerPass implements CompilerPassInterface
{

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerTaggedServices(ContainerBuilder $container)
    {
        // custom factory
        $factory = $container->findDefinition('shapecode_raas.doctrine.repository_factory');

        $repositories = [];

        // find all doctrine repository services they are tagged
        $services = $container->findTaggedServiceIds('doctrine.repository');

        // go through all
        foreach ($services as $id => $params) {
            foreach ($params as $param) {
                // add service to list
                $repositories[$param['class']] = $id;

                $className = $param['class'];
                $entityManagerServiceId = (isset($param['em'])) ? $param['em'] : 'doctrine.orm.default_entity_manager';
                $entityManagerReference = new Reference($entityManagerServiceId);
                $metaDataClassName = (isset($param['meta_class_name'])) ? $param['meta_class_name'] : 'Doctrine\ORM\Mapping\ClassMetadata';

                // get definition of service
                $repository = $container->findDefinition($id);

                // new class metadata definition
                $definition = new Definition();
                $definition->setClass($metaDataClassName);
                $definition->setFactory([$entityManagerReference, 'getClassMetadata']);
                $definition->setArguments([$className]);

                // set new arguments
                $repository->setArguments([
                    $entityManagerReference,
                    $definition
                ]);

                if (!$container->hasAlias($param['alias'])) {
                    $container->setAlias($param['alias'], $id);
                }
            }
        }

        // add services to factory ;)
        $factory->addMethodCall('addServices', [$repositories]);

        // replace default repository factory
        $container->findDefinition('doctrine.orm.configuration')->addMethodCall('setRepositoryFactory', [$factory]);
    }
}