<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;

class DefaultController extends AbstractController
{
    public function healthCheckAction()
    {
        return new JsonResponse(['data' => ['ok' => true]]);
    }
}
