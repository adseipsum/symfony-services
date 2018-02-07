<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbBlog;
use Rbl\CouchbaseBundle\Base\CbBaseModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use Rbl\CouchbaseBundle\Base\CbBaseObject;

class BlogModel extends CbBaseModel
{

    const VIEW_BY_TAGS = 'tags';

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

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getBlogListByTags($tags)
    {
        $objectIds = $this->listObjectIdByViewKey(self::VIEW_BY_TAGS, $tags);
        return $this->get(array_unique($objectIds));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getBlogTags()
    {
        $blogObjects = $this->getAllObjects();
        $tags = array();
        if($blogObjects) foreach($blogObjects as $blog){
            $tags = array_merge($tags, $blog->getTags());
        }

        return array_unique($tags);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

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
