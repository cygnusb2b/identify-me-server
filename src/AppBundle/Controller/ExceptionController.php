<?php

namespace AppBundle\Controller;

use Symfony\Bundle\TwigBundle\Controller\ExceptionController as BaseExceptionController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

class ExceptionController extends BaseExceptionController
{
    /**
     * {@inheritdoc}
     */
    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        $currentContent = $this->getAndCleanOutputBuffering($request->headers->get('X-Php-Ob-Level', -1));
        $showException = $request->attributes->get('showException', $this->debug); // As opposed to an additional parameter, this maintains BC

        $meta       = [
            'code'  => $exception->getCode(),
            'type'  => $this->getExceptionTypeFrom($exception->getClass()),
        ];
        $statusCode = $exception->getStatusCode();

        $headers = $exception->getHeaders();
        foreach ($headers as $key => $value) {
            if (0 === stripos($key, 'leads.')) {
                $metaKey = str_replace('leads.', '', $key);
                $meta[$metaKey] = $value;
                unset($headers[$key]);
            }
        }
        $exception->setHeaders($headers);

        if ($showException) {
            $meta['exception'] = $exception->toArray();
        }
        if (empty($meta)) {
            $meta = new \stdClass();
        }

        if (extension_loaded('newrelic')) {
            newrelic_notice_error($exception->getMessage(), $exception);
            newrelic_add_custom_parameter('file', $exception->getFile());
            newrelic_add_custom_parameter('line', $exception->getLine());
        }

        return new Response($this->twig->render(
            (string) $this->findTemplate($request, $request->getRequestFormat(), $statusCode, $showException),
            [
                'status_code'   => $statusCode,
                'status_text'   => isset(Response::$statusTexts[$statusCode]) ? Response::$statusTexts[$statusCode] : '',
                'exception'     => $exception,
                'logger'        => $logger,
                'currentContent'=> $currentContent,
                'meta'          => $meta,
            ]
        ));
    }

    private function getExceptionTypeFrom($className)
    {
        $parts = explode('\\', $className);
        $type  = array_pop($parts);
        return str_replace('Exception', '', $type);
    }
}
