<?php

namespace UserBundle\Entity;

use CouchbaseBundle\Base\CbBaseObject;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\GroupInterface;
use FOS\UserBundle\Model\UserInterface as FOSUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CbUser extends CbBaseObject implements UserInterface, EquatableInterface, FOSUserInterface
{

    // const ROLE_DEFAULT = 'ROLE_UNKNOWN';
    // const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';
    protected $plainPassword;

    public function __construct()
    {
        $this->set('enabled', false);
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof CbUser) {
            return false;
        }

        if ($this->get('password') !== $user->getPassword()) {
            return false;
        }

        if ($this->get('salt') !== $user->getSalt()) {
            return false;
        }

        if ($this->get('username') !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function addRole($role)
    {
        $role = strtoupper($role);
        if ($role === static::ROLE_DEFAULT) {
            return $this;
        }

        $this->addArrayElementUniq($role);
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            $this->get('password'),
            $this->get('salt'),
            $this->get('usernameCanonical'),
            $this->get('username'),
            $this->get('enabled'),
            $this->getObjectId(),
            $this->get('email'),
            $this->get('emailCanonical')
        ));
    }

    /**
     *
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        if (13 === count($data)) {
            // Unserializing a User object from 1.3.x
            unset($data[4], $data[5], $data[6], $data[9], $data[10]);
            $data = array_values($data);
        } elseif (11 === count($data)) {
            // Unserializing a User from a dev version somewhere between 2.0-alpha3 and 2.0-beta1
            unset($data[4], $data[7], $data[8]);
            $data = array_values($data);
        }

        $this->set('password', $data[0]);
        $this->set('salt', $data[1]);
        $this->set('usernameCanonical', $data[2]);
        $this->set('username', $data[3]);
        $this->set('enabled', $data[4]);
        $this->setObjectId($data[5]);
        $this->set('email', $data[6]);
        $this->set('emailCanonical', $data[7]);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        $this->set('plainPassword', null);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getObjectId();
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->get('username');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getUsernameCanonical()
    {
        return $this->get('usernameCanonical');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return $this->get('salt');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getEmail()
    {
        return $this->get('email');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getEmailCanonical()
    {
        return $this->get('emailCanonical');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->get('password');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * Gets the last login time.
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->get('lastLogin');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getConfirmationToken()
    {
        return $this->get('confirmationToken');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getRoles()
    {
        $roles = $this->getArrayElement('roles');

        foreach ($this->getGroups() as $group) {
            $roles = array_merge($roles, $group->getRoles());
        }

        // we need to make sure to have at least one role
        if (count($roles) == 0) {
            $roles[] = static::ROLE_DEFAULT;
        }
        return array_unique($roles);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function hasRole($role)
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->get('enabled');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function isSuperAdmin()
    {
        return $this->hasRole(static::ROLE_SUPER_ADMIN);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function removeRole($role)
    {
        if (false !== $key = array_search(strtoupper($role), $this->getRoles(), true)) {

            $rolesOld = $this->getRoles();
            $rolesNew = [];

            $max = count($rolesOld);
            for ($i = 0; $i < $max; $i++) {
                $elem = $rolesOld[$i];

                if ($elem === $role) { // ignore
                    continue;
                }
                $rolesNew[] = $elem;
            }
            $this->set('roles', $rolesNew);
        }

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setUsername($username)
    {
        $this->set('username', $username);

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setUsernameCanonical($usernameCanonical)
    {
        $this->set('usernameCanonical', $usernameCanonical);

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setSalt($salt)
    {
        $this->set('salt', $salt);

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setEmail($email)
    {
        $this->set('email', $email);

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setEmailCanonical($emailCanonical)
    {
        $this->set('emailCanonical', $emailCanonical);

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setEnabled($boolean)
    {
        $this->set('enabled', (bool)$boolean);

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setPassword($password)
    {
        $this->set('password', $password);

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setSuperAdmin($boolean)
    {
        if (true === $boolean) {
            $this->addRole(static::ROLE_SUPER_ADMIN);
        } else {
            $this->removeRole(static::ROLE_SUPER_ADMIN);
        }

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setPlainPassword($password)
    {

        // NOT PERSIST PLAIN PASSWORD TO DB
        $this->plainPassword = $password;
        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setLastLogin(\DateTime $time = null)
    {
        $this->set('lastLogin', $time);

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setConfirmationToken($confirmationToken)
    {
        $this->set('confirmationToken', $confirmationToken);

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setPasswordRequestedAt(\DateTime $date = null)
    {
        $this->set('passwordRequestedAt', $date);

        return $this;
    }

    /**
     * Gets the timestamp that the user requested a password reset.
     *
     * @return null|\DateTime
     */
    public function getPasswordRequestedAt()
    {
        return $this->get('passwordRequestedAt');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function isPasswordRequestNonExpired($ttl)
    {
        return $this->getPasswordRequestedAt() instanceof \DateTime && $this->getPasswordRequestedAt()->getTimestamp() + $ttl > time();
    }

    /**
     *
     * {@inheritdoc}
     */
    public function setRoles(array $roles)
    {
        $this->set('roles', array());

        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getGroups()
    {
        return $this->get('groups') ?: $this->groups = new ArrayCollection();
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getGroupNames()
    {
        $names = array();
        foreach ($this->getGroups() as $group) {
            $names[] = $group->getName();
        }

        return $names;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function hasGroup($name)
    {
        return in_array($name, $this->getGroupNames());
    }

    /**
     *
     * {@inheritdoc}
     */
    public function addGroup(GroupInterface $group)
    {
        if (!$this->getGroups()->contains($group)) {
            $this->getGroups()->add($group);
        }

        return $this;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function removeGroup(GroupInterface $group)
    {
        if ($this->getGroups()->contains($group)) {
            $this->getGroups()->removeElement($group);
        }

        return $this;
    }

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getUsername();
    }
}
