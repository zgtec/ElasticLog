<?php
declare(strict_types=1);

namespace Zgtec\ElasticLog\Models\ElasticLog;

use Illuminate\Support\Collection;
use Zgtec\ElasticLog\Models\ElasticSearch\ElasticSearch;

class ElasticLog
{
    protected $elastic;
    const INDEX = 'log-elastic';
    const TYPE = '_doc';
    const MAPPINGS = [
        'properties' => [
            "logging_time" => [
                "type" => "date",
                "format" => "yyyy-MM-dd HH:mm:ss"
            ],
        ]
    ];

    public function search(array $data = []): array
    {
        $search = $data['search'] ?? [];
        $searchColumnsIgnore = ['logging_time'];
        $should = [];
        $filter = [];

        // Searching & Sorting
        $columns = $data['columns'] ?? [];
        foreach ($columns as $column) {
            if (!empty($column['search']['value'] ?? '')) {
                if ($column['data'] === 'logging_time') {
                    $filter[] = [
                        'range' => [
                            $column['data'] => [
                                'gte' => $column['search']['value'] . ' 00:00:00',
                                'lte' => $column['search']['value'] . ' 23:59:59'
                            ]
                        ]
                    ];
                } elseif (!in_array($column['data'], $searchColumnsIgnore)) {
                    $filter[] = ['match_phrase' => [$column['data'] => $column['search']['value']]];
                    $sort['_score'] = '_score';
                }
            } elseif (!in_array($column['data'], $searchColumnsIgnore) && strlen($search['value'] ?? '') > 0) {
                $should[] = ['match_phrase' => [$column['data'] => $search['value']]];
            }
        }

        // Sorting
        foreach ($data['order'] ?? [] as $o) {
            if ($columns[$o['column']]['data'] === 'logging_time') {
                $sort[] = ['logging_time' => $o['dir']];
            } else {
                $sort[] = [$columns[$o['column']]['data'] . 'keyword' => $o['dir']];
            }

        }

        $es = new ElasticSearch(self::INDEX, self::MAPPINGS);

        $bool = ['filter' => $filter];


        if (!empty($should)) {
            $bool['should'] = $should;
            $bool['minimum_should_match'] = 1;
        }
        $es->search(
            [
                'query' => ['bool' => $bool],
                'sort' => array_values($sort ?? []),
                'from' => $data['start'] ?? 0,
                'size' => $data['length'] ?? 0,
            ]
        );
        return [
            "draw" => (int)($data['draw'] ?? 1),
            "recordsTotal" => $es->getTotalRecords(),
            "recordsFiltered" => $es->getTotalRecords(),
            "data" => $es->getHitsCollection()
        ];
    }

    public function uniqueValues(string $field, int $limit = 100, array $where = []): Collection
    {
        $es = new ElasticSearch(self::INDEX, self::MAPPINGS);

        $filter = [
            ['exists' => ['field' => 'logging_time']]
        ];

        foreach ($where as $key => $value) {
            $filter[] = ['match_phrase' => [$key => '*' . $value . '*']];
        }

        $data = [
            'query' => [
                'bool' => [
                    'filter' => $filter,
                ],
            ],
            'aggs' => [
                $field => [
                    'terms' => ['field' => $field, 'size' => $limit]
                ],
            ],
        ];
        return collect($es->search($data)->getUniqueTerms($field));
    }

    public function create(array $data): bool
    {
        $es = new ElasticSearch(self::INDEX, self::MAPPINGS);
        $response = $es->index($data);
        lad('ElasticResponse', $response);
        lad('ElasticLog', $data);
        return ($response['result'] ?? '') === 'created';
    }
    public function truncate()
    {
        $es = new ElasticSearch(self::INDEX, self::MAPPINGS);
        $es->deleteIndex();
    }


}
