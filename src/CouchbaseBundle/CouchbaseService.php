<?php

namespace CouchbaseBundle;

use Couchbase\Cluster as CouchbaseCluster;
use Couchbase\Bucket as CouchbaseBucket;

class CouchbaseService
{

    const PROD = 'prod';

    const STAGE = 'stage';

    const OPERATION_TIMEOUT = 5000000;

    /**
     * @var CouchbaseCluster
     */
    private $cluster = null;

    /**
     * @var CouchbaseBucket
     */
    private $bucketGeneral = null;

    private $config = null;

    private $envronment = CouchbaseService::STAGE;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function connectToCluster()
    {
        if ($this->cluster == null) {
            $host = $this->config['host'];
            $this->cluster = new CouchbaseCluster($host);
        }
    }

    public function setEnvironment($env)
    {
        $this->envronment = $env;
    }

    public function getGeneralBucket() : CouchbaseBucket
    {
        if ($this->bucketGeneral == null) {
            if ($this->cluster == null) {
                $this->connectToCluster();
            }

            if ($this->envronment == CouchbaseService::PROD) {
                $this->bucketGeneral = $this->cluster->openBucket('prod-workinfo', $this->config['password']);
            } else {
                $this->bucketGeneral = $this->cluster->openBucket('stage-general', $this->config['password']);
            }
            $this->bucketGeneral->operationTimeout = CouchbaseService::OPERATION_TIMEOUT;
        }
        return $this->bucketGeneral;
    }

    public function getBucketForType($objectType)
    {
        return $this->getGeneralBucket();
    }

    public function printConfig()
    {
        print_r($this->config);
    }

    public function getConfig()
    {
        return $this->config;
    }
}
