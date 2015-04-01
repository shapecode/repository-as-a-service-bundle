<?php

namespace Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class RepositoryCompilerPass
 * @package Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler
 * @author Nikita Loges
 * @date 01.04.2015
 */
class RepositoryCompilerPass implements CompilerPassInterface
{

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // custom factory
        $factory = $container->findDefinition('shapecode_raas.doctrine.repository_factory');

        $repositories = array();

        // find all doctrine repository services they are tagged
        $services = $container->findTaggedServiceIds('doctrine.repository');

        // go through all
        foreach ($services as $id => $params) {
            foreach ($params as $param) {
                // add service to list
                $repositories[$param['class']] = $id;

                // get definition of service
                $repository = $container->findDefinition($id);


                // new class metadata definition
                $definition = new Definition();
                $definition->setClass('Doctrine\ORM\Mapping\ClassMetadata');
                $definition->setFactory(array(new Reference('doctrine.orm.default_entity_manager'), 'getClassMetadata'));
                $definition->setArguments(array($param['class']));

                // set new arguments
                $repository->setArguments(array(
                    new Reference('doctrine.orm.default_entity_manager'),
                    $definition
                ));

                // set alias
                if(isset($param['alias'])) {
                    $container->addAliases(array(
                        $param['alias'] => $id
                    ));
                }
            }
        }

        // add services to factory ;)
        $factory->addMethodCall('addServices', array($repositories));

        // replace default repository factory
        $container->findDefinition('doctrine.orm.configuration')->addMethodCall('setRepositoryFactory', array($factory));
    }

}