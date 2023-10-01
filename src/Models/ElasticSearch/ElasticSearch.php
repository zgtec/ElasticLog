<?php
declare(strict_types=1);

namespace Zgtec\ElasticLog\Models\ElasticSearch;

use Illuminate\Support\Collection;

class ElasticSearch
{
    protected $client;
    protected $indexName = 'elastic';
    protected $lastResponse = [];

    public function __construct(string $indexName, array $mappings)
    {
        $this->indexName = $indexName;
        if (!$this->isIndexExists()) {
            $this->createIndex();
        }
        ElasticSearchClient::indices()->putMapping([
            'index' => $this->getIndexName(),
            'body' => $mappings
        ]);
    }

    public function getIndexName(string $suffix = ''): string
    {
        return strtolower(env('ELASTIC_PREFIX', '') . $this->indexName . $suffix);
    }

    public function index(array $data)
    {
        $params = [
            'index' => $this->getIndexName(),
            'body' => $data
        ];
        return ElasticSearchClient::index($params);
    }

    public function search(array $data): self
    {
        $params = [
            'index' => $this->getIndexName(),
            'body' => $data
        ];
        $this->lastResponse = ElasticSearchClient::search($params);
        return $this;
    }

    public function getHitsCollection(): Collection
    {
        $results = [];
        foreach ($this->getHits() as $hit) {
            $results[] = collect($hit['_source']);
        }
        return collect($results);
    }

    public function getTotalRecords(): int
    {
        return $this->lastResponse['hits']['total']['value'] ?? 0;
    }

    public function getHits(): array
    {
        return $this->lastResponse['hits']['hits'] ?? [];
    }

    public function getUniqueTerms(string $name): array
    {
        return $this->lastResponse['aggregations'][$name]['buckets'] ?? [];
    }

    public function isIndexExists(): bool
    {
        $result = ElasticSearchClient::indices()->exists([
            'index' => $this->getIndexName(),
        ]);
        return is_bool($result) ? $result : ($result->getStatusCode() === 200);
    }

    public function createIndex(): void
    {
        ElasticSearchClient::indices()->putIndexTemplate([
            'name' => $this->getIndexName(),
            'body' => [
                'index_patterns' => [$this->getIndexName() . "-*"],
                'priority' => 1,
                'template' => [
                    'settings' => [
                        'number_of_shards' => 1,
                        'number_of_replicas' => 0,
                        'index.lifecycle.name' => env('ELASTIC_LIFECYCLE', '180-days-default',),
                        'index.lifecycle.rollover_alias' => $this->getIndexName()
                    ]
                ]
            ]
        ]);

        ElasticSearchClient::indices()->create([
            'index' => $this->getIndexName('-000001'),
            'body' => [
                'aliases' => [
                    $this->getIndexName() => [
                        'is_write_index' => true
                    ]
                ]
            ]
        ]);

    }

    public function deleteIndex()
    {
        ElasticSearchClient::indices()->delete(['index' => $this->getIndexName()]);
    }
}
