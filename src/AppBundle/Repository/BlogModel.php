<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbBlog;
use CouchbaseBundle\Base\CbBaseModel;
use CouchbaseBundle\CouchbaseService;
use CouchbaseBundle\Base\CbBaseObject;

class BlogModel extends CbBaseModel
{

    const VIEW_BY_STATUS = 'status';

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Views Section
    const DISDOC_ID = "blog";

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct(CouchbaseService $service)
    {
        parent::__construct('blog', 'Blog', $service->getBucketForType('Blog'));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function factory() : CbBaseObject
    {
        $ret = new CbBlog();
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getDisdocId() : string
    {
        return self::DISDOC_ID;
    }

    public function lockBlogForPosting(CbBlog $blogObject){
        if($blogObject->getLocked()){
            return false;
        }

        if($blogObject->getLastPostDate()->getTimestamp() + $blogObject->getPostPeriodSeconds() > time()){
            return false;
        }

        $blogObject->setLocked(true);
        $this->upsert($blogObject);
        return true;
    }

}
