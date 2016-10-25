<?php

namespace Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class DefaultRepositoryServiceCompilerPass
 *
 * @package Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler
 * @author  Nikita Loges
 * @company tenolo GbR
 */
class DefaultRepositoryServiceCompilerPass implements CompilerPassInterface
{

    /**
     * @inheritdoc
     */
    public function process(ContainerBuilder $container)
    {
        $this->registerAllEntities($container);
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
            $aliasNameUnderscore = $this->generateAliasNameUnderscore($reflectionClass);
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

            if (!$container->hasAlias($aliasNameUnderscore)) {
                $container->setAlias($aliasNameUnderscore, $name);
            }
        }
    }

    /**
     * @param \ReflectionClass $classReflection
     *
     * @return string
     */
    protected function generateServiceName(\ReflectionClass $classReflection)
    {
        $namespace = $classReflection->getNamespaceName();
        $className = $classReflection->getShortName();
        $className = preg_split('/(?=[A-Z])/', $className);
        $className = array_filter($className, function ($el) {
            return !empty($el);
        });
        $className = strtolower(implode('_', $className));

        return strtolower(str_replace(['Entity\\', '\\'], ['', '.'], $namespace)) . '.' . $className . '.repository';
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return string
     */
    protected function generateAliasName(\ReflectionClass $class)
    {
        return strtolower($class->getShortName()) . '_repository';
    }

    /**
     * @param \ReflectionClass $class
     *
     * @return string
     */
    protected function generateAliasNameUnderscore(\ReflectionClass $class)
    {
        $name = $class->getShortName();
        $name = preg_split('/(?=[A-Z])/', $name);
        $name = array_filter($name, function ($el) {
            return !empty($el);
        });

        return strtolower(implode('_', $name)) . '_repository';
    }
}