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
        if (true === $this->isBot($request->headers->get('USER_AGENT'))) {
            return new JsonResponse(['data' => []]);
        }
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
            $component['props']['forms'] = [];

            foreach ($campaign->get('forms') as $form) {
                if (false === $form->get('active')) {
                    continue;
                }
                $formDef = [
                    'identifier' => $form->get('identifier'),
                    'name'   => $form->get('name'),
                    'fields' => [],
                ];
                foreach ($form->get('fields') as $field) {
                    $fieldDef = [];
                    foreach ($field->getMetadata()->getAttributes() as $key => $meta) {
                        $value = $field->get($key);
                        if (!empty($value)) {
                            $fieldDef[$key] = $value;
                        }
                    }
                    $formDef['fields'][] = $fieldDef;
                }
                if (empty($formDef['fields'])) {
                    continue;
                }
                $component['props']['forms'][] = $formDef;
            }

            if (empty($component['props']['forms'])) {
                continue;
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

            foreach ($campaign->get('cookies') as $cookie) {
                $name = $cookie->get('name');
                if (!empty($name)) {
                    $component['props']['cookies'][] = $name;
                }
            }

            $manifest[] = $component;
        }
        return new JsonResponse(['data' => $manifest]);
    }

    private function isBot($userAgent)
    {
        $patterns = ['googlebot\\/', 'Googlebot-Mobile', 'Googlebot-Image', 'Mediapartners-Google', 'bingbot', 'slurp'];
        foreach ($patterns as $pattern) {
            if (preg_match(sprintf('/%s/i', $pattern), $userAgent)) {
                return true;
            }
        }
        return false;
    }
}
