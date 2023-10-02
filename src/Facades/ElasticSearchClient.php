<?php
declare(strict_types=1);

namespace Zgtec\ElasticLog\Facades;

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
        return config('elasticlog.opensearch') ? OpenSearchClient::class : ElasticClient::class;
    }

    public static function buildClient(): OpenSearchClient|ElasticClient
    {
        $clientBuilder = config('elasticlog.opensearch') ? OpenSearchClientBuilder::create() : ElasticClientBuilder::create();
        $elastic = $clientBuilder->setHosts([config('elasticlog.elasticsearch.host')]);
        if (!empty(config('elasticlog.elasticsearch.user'))) {
            $elastic->setBasicAuthentication(config('elasticlog.elasticsearch.user'), config('elasticlog.elasticsearch.password'));
        }
        if (!config('elasticlog.ssl.verify')) {
            $elastic->setSSLVerification(false);
        }
        if (!empty(config('elasticlog.ssl.cert'))) {
            $elastic->setSSLCert(config('elasticlog.ssl.cert'));
        }
        if (config('elasticlog.aws.aoss')) {
            $elastic->setSigV4Region(config('elasticlog.aws.region'))
                ->setSigV4Service('aoss')
                ->setSigV4CredentialProvider([
                    'key' => config('elasticlog.aws.key'),
                    'secret' => config('elasticlog.aws.secret'),
                    'token' => config('elasticlog.aws.token')
                ]);
        }
        return $elastic->build();
    }

    public static function buildElasticClient(): ElasticClient
    {
        return ElasticClientBuilder::create()->build();
    }

}
