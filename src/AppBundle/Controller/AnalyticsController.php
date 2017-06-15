<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AnalyticsController extends AbstractController
{
    public function campaignAction($campaignId, $formId)
    {
        $actions = ['render', 'view', 'focus', 'submit'];
        $pipeline = [];
        $pipeline[] = ['$match' => [
            'campaign._id' => new \MongoId($campaignId),
            'data.formId'  => $formId,
            'action' => ['$in' => $actions],
        ]];
        $pipeline[] = ['$group' => [
            '_id' => '$action',
            'count' => ['$sum' => 1],
            'firstAction' => ['$min' => '$date'],
            'lastAction' => ['$max' => '$date'],
        ]];

        $report = [
            'actions'     => [],
            'conversion'  => 0,
            'labels' => [],
            'submissions' => [],
        ];
        foreach ($actions as $action) {
            // Fill with defaults
            $report['actions'][$action] = [
                'count' => 0,
                'firstAction' => null,
                'lastAction' => null,
            ];
        }

        foreach ($this->executeAggregation($pipeline) as $doc) {
            $key = $doc['_id'];
            $report['actions'][$key] = [
                'count' => $doc['count'],
                'firstAction' => date('c', $doc['firstAction']->sec),
                'lastAction' => date('c', $doc['lastAction']->sec),
            ];
        }

        if ($report['actions']['view']['count'] > 0) {
            $rate = $report['actions']['submit']['count'] / $report['actions']['view']['count'];
            if (empty($rate)) {
                $value = '0%';
            } else {
                $value = ($rate < 0.0001) ? '<0.01%' : sprintf('%s%%', round($rate * 100, 2));
            }
            $report['conversion'] = $value;
        }

        $pipeline = [];
        $pipeline[] = ['$match' => [
            'campaign._id' => new \MongoId($campaignId),
            'data.formId'  => $formId,
            'action' => 'submit',
            'data.values.mapped' => ['$exists' => true],
        ]];

        $labels = [];
        foreach ($this->executeAggregation($pipeline) as $doc) {
            $values = [];
            $mapped = $doc['data']['values']['mapped'];
            foreach ($mapped as $item) {
                $labels[$item['label']] = true;
                $values[$item['label']] = is_array($item['value']) ? $item['value']['value'] : $item['value'];
            }
            $report['submissions'][] = [
                'date' => isset($doc['date']) ? date('c', $doc['date']->sec) : null,
                'values' => $values,
                'href' => isset($doc['data']['href']) ? $doc['data']['href'] : null,
            ];
        }
        $report['labels'] = array_keys($labels);
        return new JsonResponse(['data' => $report]);
    }

    /**
     * @param   array   $pipeline
     * @param   string  $modelType
     * @return  \Doctrine\MongoDB\ArrayIterator
     */
    private function executeAggregation(array $pipeline, $modelType = 'campaign-event')
    {
        $store      = $this->getStore();
        $metadata   = $store->getMetadataForType($modelType);
        $collection = $store->getPersisterFor($modelType)->getQuery()->getModelCollection($metadata);
        return $collection->aggregate($pipeline);
    }
}
