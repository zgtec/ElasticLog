<?php
declare(strict_types=1);

namespace Zgtec\ElasticLog\Models\ElasticSearch;

use Elastic\Elasticsearch\Client as ElasticClient;
use Elastic\Elasticsearch\ClientBuilder as ElasticClientBuilder;
use Illuminate\Support\Facades\Facade;
use OpenSearch\Client as OpenSearchClient;
use OpenSearch\ClientBuilder as OpenSearchClientBuilder;


class ElasticSearchClient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return self::getClientName();
    }

    public static function getClientName(): string
    {
        return env('ELASTIC_OPENSEARCH') ? OpenSearchClient::class : ElasticClient::class;
    }

    public static function buildClient(): OpenSearchClient|ElasticClient
    {
        $clientBuilder = env('ELASTIC_OPENSEARCH') ? OpenSearchClientBuilder::create() : ElasticClientBuilder::create();
        $elastic = $clientBuilder->setHosts([env('ELASTIC_HOST')]);
        if (!empty(env('ELASTIC_USER', ''))) {
            $elastic->setBasicAuthentication(env('ELASTIC_USER'), env('ELASTIC_PASSWORD'));
        }
        if (!env('HTTP_CLIENT_VERIFY')) {
            $elastic->setSSLVerification(false);
        }
        if (!empty(env('ELASTIC_CERT', ''))) {
            $elastic->setSSLCert(env('ELASTIC_CERT'));
        }
        if (env('ELASTIC_AOSS', false)) {
            $elastic->setSigV4Region(env('ELASTIC_REGION'))
                ->setSigV4Service('aoss')
                ->setSigV4CredentialProvider([
                    'key' => env('AWS_ACCESS_KEY_ID'),
                    'secret' => env('AWS_SECRET_ACCESS_KEY'),
                    'token' => env('AWS_SESSION_TOKEN')
                ]);
        }
        return $elastic->build();
    }

    public static function buildElasticClient(): ElasticClient
    {
        return ElasticClientBuilder::create()->build();
    }

}
