<?php

namespace AppBundle\Command;

use AppBundle\Extension\SchedulerServiceExtension;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckTaskCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('task:check')->setDescription('Processed task');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cb = $this->getContainer()->get('couchbase.connector');
        $amqp = $this->getContainer()->get('old_sound_rabbit_mq.campaign_manager_producer');

        $scheduler = new SchedulerServiceExtension($cb, $amqp);

        try {
            $output->writeln($scheduler->processTask());
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
