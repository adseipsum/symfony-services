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
            $object->setClientDomain($data['clientDomain']);
            $object->setNeedPosts($data['needPosts']);
            $object->setAdditionalKeysPercentage($data['additionalKeysPercentage']);
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
                    $ret[] = array(
                        'id' => $object->getObjectId(),
                        'clientDomain' => $object->getClientDomain(),
                        'enabled' => $object->getEnabled(),
                        'status' => $object->getStatus(),
                        'needPosts' => $object->getNeedPosts(),
                        'additionalKeysPercentage' => $object->getAdditionalKeysPercentage(),
                        'postPeriodDays' => $object->getPostPeriodDays(),
                        'nextPostTime' => $object->getNextPostTime()->format('Y-m-d h:i:s'),
                        'posted' => $object->getPosted(),
                        'created' => $object->getCreated()->format('Y-m-d')
                    );
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
