<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbCampaign;
use Rbl\CouchbaseBundle\Base\CbBaseModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use Rbl\CouchbaseBundle\Base\CbBaseObject;
use AppBundle\Repository\BlogModel;

class CampaignModel extends CbBaseModel
{

    const VIEW_BY_STATUS = 'status';
    const PRE_GENERATED = 5;

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    // Views Section
    const DISDOC_ID = "campaign";

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct(CouchbaseService $service)
    {
        parent::__construct('campaign', 'Campaign', $service->getBucketForType('Campaign'));
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function factory() : CbBaseObject
    {
        $ret = new CbCampaign();
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getDisdocId() : string
    {
        return self::DISDOC_ID;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getCampaignsByStatus($status)
    {
        return $this->getObjectByView($status, self::VIEW_BY_STATUS, true);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function needsGeneration(CbCampaign $campaign){
        $postingTasks = $campaign->getNewPostingTasks();

        if($postingTasks && count($postingTasks) > self::PRE_GENERATED){
            return false;
        }

        return true;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function calculateNextPostTime(CbCampaign $campaign)
    {
        $startDate = new \DateTime();
        $endDate = $campaign->getCreated()->modify("+{$campaign->getPostPeriodDays()} day");

        $timeLeft = $endDate->getTimestamp() - $startDate->getTimestamp();
        $postsLeft = $campaign->getNeedPosts() - $campaign->getPosted();

        $postingPeriod = $postsLeft ? ceil($timeLeft / $postsLeft) : 0;

        $nextPostTime = new \DateTime();
        $nextPostTime->modify("+{$postingPeriod} seconds");
        return $nextPostTime;
    }


}
