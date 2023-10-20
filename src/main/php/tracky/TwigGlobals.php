<?php
namespace tracky;

use Symfony\Component\DependencyInjection\Container;

class TwigGlobals
{
    public function __construct(private readonly Container $container)
    {
    }
}