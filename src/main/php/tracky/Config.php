<?php
namespace tracky;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Config
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    public function get(string $key): mixed
    {
        if (!$this->container->hasParameter($key)) {
            return null;
        }

        return $this->container->getParameter($key);
    }
}