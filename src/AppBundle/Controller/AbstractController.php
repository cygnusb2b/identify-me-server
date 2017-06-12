<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

abstract class AbstractController extends Controller
{
    public function getStore()
    {
        return $this->container->get('as3_modlr.store');
    }

    protected function parseJsonPayload(Request $request)
    {
        $payload = @json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
            throw new HttpException(400, 'Invalid payload request format.');
        }
        return $payload;
    }
}
