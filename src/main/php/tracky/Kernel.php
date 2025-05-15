<?php
namespace tracky;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private function getBundlesPath(): string
    {
        return __DIR__ . "/../bundles.php";
    }

    public function getProjectDir(): string
    {
        return __DIR__ . "/../../../..";
    }
}
