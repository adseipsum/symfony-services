<?php

namespace AppBundle\Controller\FrontApi;

use AppBundle\Entity\CbCampaign;
use AppBundle\Extension\ApiResponse;
use AppBundle\Repository\CampaignModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class CampaignController extends Controller
{
    /**
     * @Route("/campaign/new", name="frontapi_campaign_new")
     * @param Request $request
     * @Method("POST")
     * @return ApiResponse
     */
    public function addNewCampaign(Request $request)
    {

        $data = json_decode($request->getContent(), true);

        try {
            $cb = $this->get('couchbase.connector');
            $model = new CampaignModel($cb);

            $object = new CbCampaign();
            $object->setEnabled(true);
            $object->setType($data['type']);

            if($data['type'] == CbCampaign::TYPE_BACKLINKED){
                $object->setMainDomain($data['mainDomain']);
                $object->setMaxPostsAtMain($data['maxPostsAtMain']);
                $object->setMainKeywords($data['mainKeywords']);
                $object->setSubLinks($data['subLinks']);
                $object->setAdditionalKeysPercentage($data['additionalKeysPercentage']);
            }

            $object->setNeedPosts($data['needPosts']);
            $object->setPostPeriodDays($data['postPeriodDays']);
            $object->setBlogs($data['selectedBlogs']);
            $object->setPosted(0);
            $object->setCreated();

            $object->setStatus(CbCampaign::STATUS_READY);
            $object->setNextPostTime($model->calculateNextPostTime($object));

            $model->upsert($object);

            return ApiResponse::resultValues(true);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route("/campaign/list", name="frontapi_campaign_list")
     * @Method("GET")
     * @return ApiResponse
     */
    public function getCampaignList()
    {
        try {
            $cb = $this->get('couchbase.connector');
            $model = new CampaignModel($cb);
            $arrayOfObjects = $model->getAllObjects();
            if ($arrayOfObjects != null){

                $ret = [];
                foreach($arrayOfObjects as $object) {
                    $campaign = array(
                        'id' => $object->getObjectId(),
                        'enabled' => $object->getEnabled(),
                        'status' => $object->getStatus(),
                        'needPosts' => $object->getNeedPosts(),
                        'postPeriodDays' => $object->getPostPeriodDays(),
                        'nextPostTime' => $object->getNextPostTime()->format('d-m-Y h:i:s'),
                        'posted' => $object->getPosted(),
                        'created' => $object->getCreated()->format('d-m-Y'),
                        'type' => $object->getType(),
                    );

                    if($object->getType() == CbCampaign::TYPE_BACKLINKED){
                        $campaign['mainDomain'] = $object->getMainDomain();
                        $campaign['maxPostsAtMain'] = $object->getMaxPostsAtMain();
                        $campaign['mainKeywords'] = $object->getMainKeywords();
                        $campaign['subLinks'] = $object->getSubLinks();
                        $campaign['additionalKeysPercentage'] = $object->getAdditionalKeysPercentage();
                    }

                    $ret[] = $campaign;
                }

                return ApiResponse::resultValue($ret);
            } else {
                return ApiResponse::resultNotFound();
            }
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}
