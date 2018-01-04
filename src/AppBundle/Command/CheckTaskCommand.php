<?php

namespace AppBundle\Command;

use AppBundle\Extension\SchedulerExtension;
use AppBundle\Entity\CbTask;
use AppBundle\Repository\TaskModel;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckTaskCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('task:check')->setDescription('Check tasks with provided status')
            ->addArgument(
                'status',
                InputArgument::REQUIRED,
                'Status of a task to check');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cb = $this->getContainer()->get('couchbase.connector');
        $amqp = $this->getContainer()->get('old_sound_rabbit_mq.task_manager_producer');

        $scheduler = new SchedulerExtension($cb, $amqp);

        try {
            $output->writeln($scheduler->processTasks($input->getArgument('status')));
        } catch (Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
