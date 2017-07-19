<?php

namespace AppBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Rbl\CouchbaseBundle\RblCouchbaseBundle;
use UserBundle\Entity\CbUser;
use UserBundle\Repository\UserModel;

class SandboxCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('sandbox')
            ->setDescription('Sandbox');

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {


        try {
            $cb = $this->getContainer()->get('couchbase.connector');


            $uModel = new UserModel($cb);

            $user = new CbUser();
            $user->setEmail('alexey.kruchenok@gmail.com');
            $user->setUsername('lpvoid');
            $user->setPassword('123456');

            $uModel->upsert($user);
        }
        catch(Exception $e)
        {
            $output->println($e->getMessage());
        }
    }


}
