<?php

namespace OAuthServerBundle\Command;

use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class CreateOauthClientCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this->setName('oauthserver:client:create')->setDescription('Setup oauth client');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();
        $client->setRedirectUris(array('https://api.aitext.me/oauth/oauth-callback'));
        $client->setAllowedGrantTypes(array('password','token', 'authorization_code'));
        $clientManager->updateClient($client);
    }
}
