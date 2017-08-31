<?php

namespace Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Shapecode\Bundle\RasSBundle\DependencyInjection\ServiceNameGenerator;
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

        // custom factory
        $factory = $container->findDefinition('shapecode_raas.doctrine.repository_factory');

        $repositories = [];

        $nameGenerator = new ServiceNameGenerator();

        /** @var array|ClassMetadata[] $metadata */
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $m) {
            $id = $nameGenerator->getServiceName($em, $m);

            $repositoryName = EntityRepository::class;

            if ($m->customRepositoryClassName) {
                $repositoryName = $m->customRepositoryClassName;
            }

            $className = $m->getReflectionClass()->getName();

            $alias = $nameGenerator->getAliasName($className);

            if ($container->has($alias) || $container->hasAlias($alias)) {
                continue;
            }

            if (!$container->has($id)) {
                $definition = new Definition($repositoryName);
                $definition->setFactory([
                    new Reference('shapecode_raas.doctrine.repository_factory.default'),
                    'getRepository'
                ]);
                $definition->addArgument(new Reference('doctrine.orm.default_entity_manager'));
                $definition->addArgument($m->getName());
                $container->setDefinition($id, $definition);
            }

            // add service to list
            $repositories[$className] = $id;

            $container->setAlias($alias, $id);
        }

        // add services to factory ;)
        $factory->addMethodCall('addServices', [$repositories]);
    }
}