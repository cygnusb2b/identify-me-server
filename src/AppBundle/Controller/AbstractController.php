<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

abstract class AbstractController extends Controller
{
    public function getStore()
    {
        return $this->container->get('as3_modlr.store');
    }
}
