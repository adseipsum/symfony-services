<?php

namespace AppBundle\Entity;

use CouchbaseBundle\Base\CbBaseObject;

class CbTask extends CbBaseObject
{

    const STATUS_NEW = 'new';
    const STATUS_GENERATION = 'generation';
    const STATUS_POSTING = 'posting';
    const STATUS_COMPLETE = 'complete';

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct();
        $this->setCreated(new \DateTime());
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setStatus(string $status)
    {
        $this->set('status', $status);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getStatus() : string
    {
        return $this->get('status');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setEnabled(bool $enabled)
    {
        $this->set('enabled', $enabled);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getEnabled() : bool
    {
        return $this->get('enabled');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setDomainName(string $domainName)
    {
        $this->set('domainName', $domainName);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getDomainName() : string
    {
        return $this->get('domainName');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setCreated(\DateTime $time = null)
    {
        if(!$time){
            $time = new \DateTime();
            $time->format('U');
        }

        $this->set('created', $time->getTimestamp());

    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getCreated() : \DateTime
    {
        $ret = new \DateTime();
        $unixtimestamp = $this->get('created');
        $ret->setTimestamp($unixtimestamp);
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setExpired(\DateTime $time = null)
    {
        $this->set('expired', $time->getTimestamp());
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getExpired() : \DateTime
    {
        $ret = new \DateTime();
        $unixtimestamp = $this->get('expired');
        $ret->setTimestamp($unixtimestamp);
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setAge(int $age)
    {
        $this->set('age', $age);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getAge() : int
    {
        return $this->get('age');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setNeedPosts(int $needPosts)
    {
        $this->set('needPosts', $needPosts);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getNeedPosts() : int
    {
        return $this->get('needPosts');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setAdditionalKeysPercentage(float $additionalKeysPercentage)
    {
        $this->set('additionalKeysPercentage', $additionalKeysPercentage);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getAdditionalKeysPercentage() : float
    {
        return $this->get('additionalKeysPercentage');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setPostPeriodDays(int $postPeriodDays)
    {
        $this->set('postPeriodDays', $postPeriodDays);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getPostPeriodDays() : int
    {
        return $this->get('postPeriodDays');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setNextPost(\DateTime $time = null)
    {
        $this->set('nextPost', $time->getTimestamp());
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getNextPost() : \DateTime
    {
        $ret = new \DateTime();
        $unixtimestamp = $this->get('nextPost');
        $ret->setTimestamp($unixtimestamp);
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setPosted(int $posted)
    {
        $this->set('posted', $posted);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getPosted() : int
    {
        return $this->get('posted');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setErrors(int $errors)
    {
        $this->set('errors', $errors);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getErrors() : int
    {
        return $this->get('errors');
    }


    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////
}
