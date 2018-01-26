<?php

namespace UserBundle\Repository;

use Rbl\CouchbaseBundle\Base\CbDirectKeyModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use Symfony\Component\Security\Core\Encoder\BCryptPasswordEncoder;
use UserBundle\Entity\CbUser;

class UserModel extends CbDirectKeyModel
{

    // Views Section
    const DISDOC_ID = "user";

    const VIEW_BY_USERNAME = 'username';

    public function __construct(CouchbaseService $service)
    {
        parent::__construct('usr', 'User', $service->getBucketForType('User'));
    }

    public function factory()
    {
        return new CbUser();
    }

    public function getDisdocId()
    {
        return self::DISDOC_ID;
    }

    public function upsert($object, $id = null, $ttl = 0)
    {
        if ($id == null && $object->getObjectId() == null) {
            $key = $object->getUsernameCanonical();
            $object->setObjectId($this->key($key));
        }
        parent::upsert($object, $id, $ttl);
    }

    public function insert($object, $id = null, $ttl = 0)
    {
        if ($id == null && $object->getObjectId() == null) {
            $object->setObjectId($this->key($object->getUsername()));
        }
        parent::insert($object, $id, $ttl);
    }

    public function getUserByUsername($username)
    {
        return $this->getObjectByView($username, self::VIEW_BY_USERNAME, true);
    }

    public function getSalt()
    {
        return base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    }

    public function encodePassword($password, $salt)
    {
        $encoder = new BCryptPasswordEncoder(8);
        return $encoder->encodePassword($password, $salt);
    }

    public function veryPassword($encoded, $raw, $salt)
    {
        $encoder = new BCryptPasswordEncoder(8);
        return $encoder->isPasswordValid($encoded, $raw, $salt);
    }
}
