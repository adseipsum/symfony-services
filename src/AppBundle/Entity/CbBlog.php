<?php

namespace AppBundle\Entity;

use Rbl\CouchbaseBundle\Base\CbBaseObject;

class CbBlog extends CbBaseObject
{

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct();
        $this->setRecordCreated(new \DateTime());
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

    public function setRecordCreated(\DateTime $time = null)
    {
        if(!$time){
            $time = new \DateTime();
            $time->format('U');
        }

        $this->set('recordCreated', $time->getTimestamp());

    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getRecordCreated() : \DateTime
    {
        $ret = new \DateTime();
        $unixtimestamp = $this->get('recordCreated');
        $ret->setTimestamp($unixtimestamp);
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setPostingUserLogin(string $postingUserLogin)
    {
        $this->set('postingUserLogin', $postingUserLogin);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getPostingUserLogin() : string
    {
        return $this->get('postingUserLogin');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setPostingUserPassword(string $postingUserPassword)
    {
        $this->set('postingUserPassword', $postingUserPassword);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getPostingUserPassword() : string
    {
        return $this->get('postingUserPassword');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setClientId(string $clientId)
    {
        $this->set('clientId', $clientId);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getClientId() : string
    {
        return $this->get('clientId');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setClientSecret(string $clientSecret)
    {
        $this->set('clientSecret', $clientSecret);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getClientSecret() : string
    {
        return $this->get('clientSecret');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setLastPostId(int $lastPostId)
    {
        $this->set('lastPostId', $lastPostId);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getLastPostId() : int
    {
        return $this->get('lastPostId');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setLastPostDate(\DateTime $time)
    {
        $this->set('lastPostDate', $time->getTimestamp());
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getLastPostDate() : \DateTime
    {
        $ret = new \DateTime();
        $unixtimestamp = $this->get('lastPostDate');
        $ret->setTimestamp($unixtimestamp);
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setLocked(bool $isLocked)
    {
        $this->set('locked', $isLocked);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getLocked() : bool
    {
        return $this->get('locked');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setPostPeriodSeconds()
    {
        $this->set('postPeriodSeconds', 9600);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getPostPeriodSeconds() : int
    {
        return $this->get('postPeriodSeconds');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setPostsCounter()
    {
        $this->set('postsCounter', $this->get('postsCounter') + 1);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getPostsCounter() : int
    {
        return $this->get('postsCounter');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setTags(array $tags)
    {
        $this->set('tags', $tags);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getTags() : array
    {
        return $this->get('tags');
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

    public function setDomainExpiredDate(\DateTime $time = null)
    {
        $this->set('domainExpiredDate', $time->getTimestamp());
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getDomainExpiredDate() : \DateTime
    {
        $ret = new \DateTime();
        $unixtimestamp = $this->get('domainExpiredDate');
        $ret->setTimestamp($unixtimestamp);
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setLastErrorMessage(string $lastErrorMessage)
    {
        $this->set('lastErrorMessage', $lastErrorMessage);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getLastErrorMessage() : string
    {
        return $this->get('lastErrorMessage');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setLastTypePosted(string $lastTypePosted)
    {
        $this->set('lastTypePosted', $lastTypePosted);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getLastTypePosted() : string
    {
        return $this->get('lastTypePosted');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function setMainDomainLinksPosted(array $mainDomainLinkPosted)
    {
        $this->set('mainDomainLinksPosted', $mainDomainLinkPosted);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getMainDomainLinksPosted() : array
    {
        if($this->get('mainDomainLinksPosted')){
            return $this->get('mainDomainLinksPosted');
        }
        return array();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function isBlogReadyForPosting(){
        if($this->getLastPostDate()->getTimestamp() + $this->getPostPeriodSeconds() > time()){
            return false;
        }
        return true;
    }



}
