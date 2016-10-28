<?php

namespace Shapecode\Bundle\RasSBundle\DependencyInjection\Compiler;

use Doctrine\ORM\EntityManagerInterface;
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

        // custom factory
        $factory = $container->findDefinition('shapecode_raas.doctrine.repository_factory');

        $repositories = [];

        /** @var array|ClassMetadata[] $metadata */
        $metadata = $em->getMetadataFactory()->getAllMetadata();
        foreach ($metadata as $m) {
            $id = $this->generateServiceName($em, $m);

            $repositoryName = EntityRepository::class;

            if ($m->customRepositoryClassName) {
                $repositoryName = $m->customRepositoryClassName;
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
            $repositories[$m->getReflectionClass()->getName()] = $id;

            $aliasParts = $this->getAliasParts($em, $m);
            $aliasName = strtolower(implode('', $aliasParts)).'_repository';
            $aliasNameUnderscore = strtolower(implode('_', $aliasParts)).'_repository';

            if (!$container->hasAlias($aliasName)) {
                $container->setAlias($aliasName, $id);
            }

            if ($aliasNameUnderscore != $aliasName) {
                if (!$container->hasAlias($aliasNameUnderscore)) {
                    $container->setAlias($aliasNameUnderscore, $id);
                }
            }
        }

        // add services to factory ;)
        $factory->addMethodCall('addServices', [$repositories]);
    }

    /**
     * @param EntityManagerInterface $em
     * @param ClassMetadata          $m
     *
     * @return string
     */
    protected function generateServiceName(EntityManagerInterface $em, ClassMetadata $m)
    {
        $classReflection = $m->getReflectionClass();
        $classNamespace = $classReflection->getNamespaceName();

        $aliasData = $this->getNamespaceAlias($em, $classNamespace);
        $namespace = $aliasData['namespace'];
        $namespaceAlias = $aliasData['alias'];

        $namespaceBlacklist = [
            'entity',
            'entities',
            'bundle',
        ];

        $className = $classReflection->getName();
        $className = str_replace($namespace, '', $className);
        $className = str_replace('\\', '', $className);
        $className = preg_split('/(?=[A-Z])/', $className);
        $className = array_filter($className, function ($el) {
            return !empty($el);
        });
        $className = strtolower(implode('_', $className));

        $namespaceAlias = preg_split('/(?=[A-Z])/', $namespaceAlias);
        $namespaceAlias = array_map(function ($el) {
            return strtolower($el);
        }, $namespaceAlias);
        $namespaceAlias = array_filter($namespaceAlias, function ($el) use ($namespaceBlacklist) {
            if (empty($el)) {
                return false;
            }

            if (in_array($el, $namespaceBlacklist)) {
                return false;
            }

            return true;
        });
        $namespaceAlias = strtolower(implode('_', $namespaceAlias));

        $serviceName = $namespaceAlias . '.repository.' . $className;

        return $serviceName;
    }

    /**
     * @param EntityManagerInterface $em
     * @param ClassMetadata          $m
     *
     * @return array|mixed|string
     */
    protected function getAliasParts(EntityManagerInterface $em, ClassMetadata $m)
    {

        $class = $m->getReflectionClass();
        $classNamespace = $class->getNamespaceName();

        $aliasData = $this->getNamespaceAlias($em, $classNamespace);

        $className = $class->getName();
        $className = str_replace([$aliasData['namespace'], '\\'], '', $className);
        $className = preg_split('/(?=[A-Z])/', $className);
        $className = array_filter($className, function ($el) {
            return !empty($el);
        });

        return $className;
    }

    /**
     * @param EntityManagerInterface $em
     * @param                        $baseNamespace
     *
     * @return array
     */
    protected function getNamespaceAlias(EntityManagerInterface $em, $baseNamespace)
    {
        $namespaces = $em->getConfiguration()->getEntityNamespaces();

        $namespace = null;
        foreach ($namespaces as $alias => $namespace) {
            if (mb_substr($baseNamespace, 0, mb_strlen($namespace)) === $namespace) {
                return [
                    'namespace' => $namespace,
                    'alias'     => $alias
                ];
            }
        }
    }
}