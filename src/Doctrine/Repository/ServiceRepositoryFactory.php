<?php

namespace Shapecode\Bundle\RasSBundle\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Repository\RepositoryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ServiceRepositoryFactory
 *
 * @package Shapecode\Bundle\RasSBundle\Doctrine\Repository
 * @author  Nikita Loges
 */
class ServiceRepositoryFactory implements RepositoryFactory
{

    /** @var array */
    protected $ids;

    /** @var ContainerInterface */
    protected $container;

    /** @var RepositoryFactory */
    protected $default;

    /**
     * @param ContainerInterface $container
     * @param RepositoryFactory  $default
     */
    public function __construct(ContainerInterface $container, RepositoryFactory $default)
    {
        $this->container = $container;
        $this->default = $default;
    }

    /**
     * @param array $services
     */
    public function addServices(array $services)
    {
        foreach ($services as $entityName => $service) {
            $this->addService($entityName, $service);
        }
    }

    /**
     * @param $entityName
     * @param $service
     *
     * @return mixed
     */
    public function addService($entityName, $service)
    {
        if (!isset($this->ids[$entityName])) {
            return $this->ids[$entityName] = $service;
        }
    }

    /**
     * @param $className
     *
     * @return bool
     */
    public function hasService($className)
    {
        return (isset($this->ids[$className]));
    }

    /**
     * @param $className
     *
     * @return mixed
     */
    public function getService($className)
    {
        return $this->ids[$className];
    }

    /**
     * @inheritdoc
     */
    public function getRepository(EntityManagerInterface $entityManager, $entityName)
    {
        $reflection = $entityManager->getClassMetadata($entityName)->getReflectionClass();
        $className = $reflection->getName();

        if ($this->hasService($className)) {
            return $this->container->get($this->getService($className));
        }

        return $this->default->getRepository($entityManager, $entityName);
    }
}