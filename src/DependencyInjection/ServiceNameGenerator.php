<?php

namespace Shapecode\Bundle\RasSBundle\DependencyInjection;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Class ServiceNameGenerator
 *
 * @package Shapecode\Bundle\RasSBundle\DependencyInjection
 * @author  Nikita Loges
 */
class ServiceNameGenerator
{

    /**
     * @param EntityManagerInterface $em
     * @param ClassMetadata          $m
     *
     * @return string
     */
    public function getServiceName(EntityManagerInterface $em, ClassMetadata $m)
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
     * @param $class
     *
     * @return mixed|string
     */
    public function getAliasName($class)
    {
        $reflection = new \ReflectionClass($class);

        $className = $reflection->getName();
        $className = str_replace('\\', '_', $className);
        $className = strtolower($className);
        $className = str_replace(['bundle', '_entity', '_entities'], '', $className);

        return 'raas_' . $className . '_repository';
    }

    /**
     * @param EntityManagerInterface $em
     * @param                        $baseNamespace
     *
     * @return array
     */
    public function getNamespaceAlias(EntityManagerInterface $em, $baseNamespace)
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
