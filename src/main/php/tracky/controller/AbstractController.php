<?php
namespace tracky\controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use tracky\model\User;

abstract class AbstractController extends BaseController
{
    protected function getUser(): ?User
    {
        return parent::getUser();
    }
}
