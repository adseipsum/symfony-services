<?php

namespace AppBundle\Consumer;

use AppBundle\Extension\BacklinkServiceExtension;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;


class BacklinkServiceConsumer implements ConsumerInterface
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
        $amqp = $this->container->get('old_sound_rabbit_mq.backlink_service_producer');

        $backlinkService = new BacklinkServiceExtension($cb, $amqp);

        $backlinkService->processMessage($msg);
    }
}