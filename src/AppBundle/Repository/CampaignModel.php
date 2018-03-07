<?php

namespace AppBundle\Repository;

use AppBundle\Entity\CbCampaign;
use Rbl\CouchbaseBundle\Base\CbBaseModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use Rbl\CouchbaseBundle\Base\CbBaseObject;

class CampaignModel extends CbBaseModel
{

    const VIEW_BY_STATUS = 'status';
    const VIEW_BY_TYPE = 'type';

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
        return $this->getObjectsByView(self::VIEW_BY_STATUS, $status);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getCampaignForPosting($type)
    {
        return $this->getObjectByView($type, self::VIEW_BY_TYPE);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function calculateNextPostTime(CbCampaign $campaign)
    {
        $startDate = $campaign->getCreated();
        $endDate = $campaign->getCreated()->modify("+{$campaign->getPostPeriodDays()} day");

        $timeLeft = $endDate->getTimestamp() - $startDate->getTimestamp();
        $postsLeft = $campaign->getNeedPosts() - $campaign->getPosted();

        $postingPeriod = $postsLeft ? ceil($timeLeft / $postsLeft) : 0;

        $nextPostTime = new \DateTime();
        $nextPostTime->modify("+{$postingPeriod} seconds");

        return $nextPostTime;
    }


}
