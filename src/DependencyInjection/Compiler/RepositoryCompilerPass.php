<?php

namespace Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityRepository;
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
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerTaggedServices($container);
        $this->registerAllEntities($container);
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

                // set alias
                if (isset($param['alias'])) {
                    $container->addAliases([
                        $param['alias'] => $id
                    ]);
                }
            }
        }

        // add services to factory ;)
        $factory->addMethodCall('addServices', [$repositories]);

        // replace default repository factory
        $container->findDefinition('doctrine.orm.configuration')->addMethodCall('setRepositoryFactory', [$factory]);
    }

    /**
     * @param ContainerBuilder $container
     */
    protected function registerAllEntities(ContainerBuilder $container)
    {
        if (!$container->has('doctrine.orm.default_entity_manager')) {
            return;
        }

        $em = $container->get('doctrine.orm.default_entity_manager');

        /** @var array|ClassMetadata[] $metadata */
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $m) {
            $reflectionClass = $m->getReflectionClass();

            $name = $this->generateServiceName($reflectionClass);
            $aliasName = $this->generateAliasName($reflectionClass);
            $repositoryName = EntityRepository::class;

            if ($m->customRepositoryClassName) {
                $repositoryName = $m->customRepositoryClassName;
            }

            if (!$container->has($name)) {
                $definition = new Definition($repositoryName);
                $definition->setFactory([
                    new Reference('doctrine.orm.default_entity_manager'),
                    'getRepository'
                ]);
                $definition->addArgument($m->getName());
                $container->setDefinition($name, $definition);
            }

            if (!$container->hasAlias($aliasName)) {
                $container->setAlias($aliasName, $name);
            }
        }
    }

    /**
     * @param \ReflectionClass $class
     * @return string
     */
    protected function generateServiceName(\ReflectionClass $class)
    {
        return strtolower(str_replace(['Entity\\', '\\'], ['', '.'], $class->getName())) . '.repository';
    }

    /**
     * @param \ReflectionClass $class
     * @return string
     */
    protected function generateAliasName(\ReflectionClass $class)
    {
        return strtolower($class->getShortName()) . '_repository';
    }

}