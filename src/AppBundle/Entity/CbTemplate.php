<?php

namespace AppBundle\Entity;

use CouchbaseBundle\Base\CbBaseObject;
use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\GroupableInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use FOS\UserBundle\Model\UserInterface as FOSUserInterface;
use Symfony\Component\Validator\Constraints as Assert;


class CbTemplate extends CbBaseObject
{

    public function setArchived($isArchived)
    {
        $this->set('isArchived', $isArchived);
    }

    public function isArchived()
    {
        $ret = $this->get('isArchived');
        if($ret == null)
        {
            return false;
        }
        else {
            return $ret;
        }
    }

    public function setCount($count)
    {
        $this->set('count', $count);
    }

    public function getCount()
    {
        $ret = $this->get('count');
        if($ret == null)
        {
            return 0;
        }
        else {
            return $ret;
        }
    }

    public function incCount()
    {
        $count = $this->getCount();
        $this->setCount($count+1);
    }

    public function decCount()
    {
        $count = $this->getCount();
        $this->setCount($count-1);
    }

    public function setTemplate($text)
    {
        $this->set('templateText', $text);
    }

    public function getTemplate()
    {
        return $this->get('templateText');
    }

    public function setName($name)
    {
        $this->set('name', $name);
    }

    public function getName()
    {
        return $this->get('name');
    }



}
