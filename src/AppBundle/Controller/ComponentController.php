<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ComponentController extends AbstractController
{
    public function analyticsAction(Request $request)
    {
        $payload = $this->parseJsonPayload($request);
        $data = $payload['data'];
        try {
            $model = $this->getStore()->create('campaign-event');
            $model->set('action', $data['action']);
            $model->set('date', new \MongoDate());
            $model->set('data', $data['data']);

            $campaign = $this->getStore()->find('campaign', $data['identifier']);
            $model->set('campaign', $campaign);
            $model->save();
            return new JsonResponse(['data' => ['inserted' => $model->getId()]]);
        } catch (\Exception $e) {
            return new JsonResponse(['data' => ['inserted' => false]], 400);
        }
    }

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
            'deleted'      => false,
            'targets.host' => $data['location']['hostname'],
            '$or'          => [
                ['targets.path' => ['$exists' => false]],
                ['targets.path' => $data['location']['pathname']],
            ],
        ];


        $manifest = [];
        $cursor = $this->getStore()->findQuery('campaign', $criteria);
        foreach ($cursor as $campaign) {
            $component = [
                'type'      => $campaign->getType(),
                'selectors' => [],
                'props'     => [],
            ];

            $props = $campaign->get('props');
            if (null !== $props) {
                foreach ($props->getMetadata()->getAttributes() as $key => $meta) {
                    $value = $props->get($key);
                    if (!empty($value)) {
                        $component['props'][$key] = $value;
                    }
                }
            }
            $component['props']['id'] = $campaign->getId();

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
