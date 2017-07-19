<?php
namespace UserBundle\Service;

use CouchbaseBundle\CouchbaseService;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use UserBundle\Entity\CbUser;
use UserBundle\Repository\UserModel;

class CbUserProvider implements UserProviderInterface
{
    protected $cb;

    public function __construct(CouchbaseService $cb)
    {
        $this->cb = $cb;
    }

    public function loadUserByUsername($username)
    {
        $mUser = new UserModel($this->cb);

        $user = $mUser->getSingle($username);

        if($user != null)
        {
            return $user;
        }
        else {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $username)
            );
        }
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof CbUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }
        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return CbUser::class === $class;
    }
}