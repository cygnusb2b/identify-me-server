<?php

namespace AppBundle\Controller;

use As3\OmedaSDK\ApiClient as OmedaClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class IntegrationController extends AbstractController
{
    public function serviceTestAction($type, Request $request)
    {
        $payload = @json_decode($request->getContent(), true);
        if (!is_array($payload) || !isset($payload['data']) || !is_array($payload['data'])) {
            throw new HttpException(400, 'Invalid payload request format.');
        }
        $data = $payload['data'];
        switch ($type) {
            case 'omeda':
                $client = new OmedaClient($data);
                if (false === $client->hasValidConfig()) {
                    throw new HttpException(400, 'The Omeda API configuration is invalid.');
                }
                $response = $client->brand->lookup();
                if (200 === $response->getStatusCode()) {
                    return new JsonResponse(['data' => ['ok' => true]]);
                }
                throw new HttpException(400, 'Unable to rertieve a successful response from the Omeda API.');
            default:
                throw new HttpException(400, 'The provided integration service type is not supported.');
        }
    }

    public function deploymentTypesAction($serviceId)
    {
        $service = $this->getStore()->find('integration-service', $serviceId);
        switch ($service->getType()) {
            case 'integration-service-omeda':
                $options = [];
                foreach (['clientKey', 'appId', 'inputId', 'brandKey'] as $key) {
                    $options[$key] = $service->get($key);
                }
                $client = new OmedaClient($options);
                $response = $client->parseApiResponse($client->brand->lookup());
                $types = [];
                foreach ($response['Products'] as $product) {
                    if (2 !== $product['ProductType'] && 5 !== $product['ProductType']) {
                        continue;
                    }
                    $types[] = [
                        'label' => 'Omeda',
                        'service' => 'omeda',
                        'identifier' => (string) $product['Id'],
                        'name' => $product['Description'],
                    ];
                }
                return new JsonResponse(['data' => $types]);
            default:
                throw new HttpException(400, 'The provided integration service type is not yet supported.');
        }
    }
}
