<?php

namespace OAuthServerBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use OAuthServerBundle\DependencyInjection\Compiler\OverrideAuthenticationProviderCompilerPass;

class OAuthServerBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new OverrideAuthenticationProviderCompilerPass());
    }

    public function getParent()
    {
        return 'FOSOAuthServerBundle';
    }
}
