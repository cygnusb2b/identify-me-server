<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ComponentController extends AbstractController
{
    public function manifestAction(Request $request)
    {
        $payload = $this->parseJsonPayload($request);
        $data = $payload['data'];

        if (!isset($data['location']['hostname']) || empty($data['location']['hostname'])) {
            throw new HttpException(400, 'Improper manifest format. Must include a location hostname.');
        }
        if (!isset($data['location']['pathname']) || empty($data['location']['pathname'])) {
            throw new HttpException(400, 'Improper manifest format. Must include a location pathname.');
        }
        $criteria = [
            'targets.host' => $data['location']['hostname'],
            '$or'          => [
                ['targets.path' => ['$exists' => false]],
                ['targets.path' => $data['location']['pathname']],
            ],
        ];


        $manifest = [];
        $cursor = $this->getStore()->findQuery('email-signup-campaign', $criteria);
        foreach ($cursor as $campaign) {
            $component = [
                'name'      => 'EmailSignupCampaign',
                'selectors' => [],
                'props'     => ['id' => $campaign->getId()],
            ];

            foreach (['callToAction', 'description', 'buttonValue', 'previewUrl'] as $key) {
                $value = $campaign->get($key);
                if (!empty($value)) {
                    $component['props'][$key] = $value;
                }
            }

            foreach ($campaign->get('targets') as $target) {
                if ($target->get('host') === $data['location']['hostname']) {
                    if (null === $target->get('path') || $target->get('path') === $data['location']['pathname']) {
                        $selector = $target->get('selector');
                        if (!empty($selector)) {
                            $component['selectors'][] = $selector;
                        }
                    }
                }
            }

            $manifest[] = $component;
        }
        return new JsonResponse(['data' => $manifest]);
    }
}
