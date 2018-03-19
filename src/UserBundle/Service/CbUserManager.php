<?php
/**
 * Created by PhpStorm.
 * User: void
 * Date: 7/19/17
 * Time: 6:04 PM
 */

namespace UserBundle\Service;

use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use Rbl\CouchbaseBundle\CouchbaseService;
use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Model\UserManager;
use FOS\UserBundle\Util\CanonicalFieldsUpdater;
use FOS\UserBundle\Util\Canonicalizer;
use FOS\UserBundle\Util\PasswordUpdater;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use UserBundle\Entity\CbUser;
use UserBundle\Repository\UserModel;

class CbUserManager extends UserManager
{
    protected $cb;
    protected $mdUserAuth;
    protected $unknownuserdomain;

    public function __construct(CouchbaseService $cb,
                                EncoderFactoryInterface $encoder)
    {
        $this->cb = $cb;
        $this->mdUserAuth = new UserModel($cb);

        $passwordUpdater = new PasswordUpdater($encoder);
        $canonicalizer = new Canonicalizer();
        $canonicalFieldUpdater = new CanonicalFieldsUpdater($canonicalizer, $canonicalizer);

        parent::__construct($passwordUpdater, $canonicalFieldUpdater);
    }

    /**
     * Deletes a user.
     *
     * @param UserInterface $user
     */
    public function deleteUser(UserInterface $user)
    {
        #todo: delete profile also

        $this->mdUserAuth->remove($user->getUsername());
    }


    /**
     * {@inheritdoc}
     */
    public function findUserByUsername($username)
    {
        return $this->findUserBy(array('username' => $username));
    }
    /**
     * Finds one user by the given criteria.
     *
     * @param array $criteria
     *
     * @return UserInterface
     */
    public function findUserBy(array $criteria)
    {

        if(isset($criteria['username']))
        {
            return $this->mdUserAuth->getUserByUsername($criteria['username']);
        }
        else if(isset($criteria['usernameCanonical']))
        {
            return $this->mdUserAuth->getUserByUsername($criteria['usernameCanonical']);
        }
        else if(isset($criteria['email']))
        {
            return $this->mdUserAuth->getUserByEmail($criteria['email']);
        }
        else if(isset($criteria['emailCanonical']))
        {
            return $this->mdUserAuth->getUserByEmail($criteria['emailCanonical']);
        }

        return null;
    }

    /**
     * Returns a collection with all user instances.
     *
     * @return \Traversable
     */
    public function findUsers()
    {
        return $this->mdUserAuth->getAllObjects();
    }

    /**
     * Returns the user's fully qualified class name.
     *
     * @return string
     */
    public function getClass()
    {
        return CbUser::class;
    }

    /**
     * Reloads a user.
     *
     * @param UserInterface $user
     */
    public function reloadUser(UserInterface $user)
    {
        $this->mdUserAuth->getUserByUsername($user->getUsername());
    }

    public function getSalt()
    {
        return base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    }

    /**
     * Updates a user.
     *
     * @param UserInterface $user
     */
    public function updateUser(UserInterface $user)
    {
 //       if($user->getSalt() == null)
 //       {
 //            $user->setSalt($this->getSalt());
 //       }

        $this->updateCanonicalFields($user);
        $this->updatePassword($user);

        $this->mdUserAuth->upsert($user);
        $email = $user->getEmail();

    }


}