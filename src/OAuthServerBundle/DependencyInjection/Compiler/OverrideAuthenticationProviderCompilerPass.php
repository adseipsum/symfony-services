<?php

namespace OAuthServerBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use OAuthServerBundle\Security\Authentication\Provider\OAuthProvider;

class OverrideAuthenticationProviderCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('fos_oauth_server.security.authentication.provider');
        $definition->setClass(OAuthProvider::class);
    }
}