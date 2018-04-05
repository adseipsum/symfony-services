<?php

namespace AppBundle\Consumer;

use AppBundle\Extension\CampaignManagerServiceExtension;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;


class CampaignManagerConsumer implements ConsumerInterface
{
    private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    /**
     * @var AMQPMessage $msg
     * @return void
     */
    public function execute(AMQPMessage $msg)
    {
        $cb = $this->container->get('couchbase.connector');
        $amqp = $this->container->get('old_sound_rabbit_mq.campaign_manager_service_producer');

        $campaignManager = new CampaignManagerServiceExtension($cb, $amqp);

        $campaignManager->processMessage($msg);
    }
}