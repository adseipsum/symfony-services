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
     * @Route("/campaign/upsert", name="frontapi_campaign_upsert")
     * @param Request $request
     * @Method("POST")
     * @return ApiResponse
     */
    public function upsertCampaign(Request $request)
    {

        $data = json_decode($request->getContent(), true);

        try {
            $cb = $this->get('couchbase.connector');
            $model = new CampaignModel($cb);

            if(isset($data['campaignId']) && is_string($data['campaignId']) && strpos($data['campaignId'], 'campaign') === 0) {
                $object = $model->get($data['campaignId']);
                $object->setPostMainDomainLinks(0);
                $object->setPostSubLinks(0);
            }else{
                $object = new CbCampaign();
                $object->setEnabled(true);
                $object->setType($data['type']);
                $object->setPosted(0);
                $object->setCreated();
                $object->setStatus(CbCampaign::STATUS_READY);
                $object->setNextPostTime(new \DateTime());
            }

            if($data['type'] == CbCampaign::TYPE_BACKLINKED){
                $object->setMainDomain($data['mainDomain']);
                $object->setAdditionalKeysPercentage($data['additionalKeysPercentage']);
                $object->setPostMainDomainLinks($data['postMainDomainLinks']);
                $object->setPostSubLinks($data['postSubLinks']);
                $object->setMainKeywords($data['mainKeywords']);
                $object->setSubLinks($data['subLinks']);
                $object->setNeedPosts($data['postSubLinks'] + $data['postMainDomainLinks']);
            }else{
                $object->setPostMainDomainLinks(0);
                $object->setPostSubLinks(0);
                $object->setNeedPosts($data['needPosts']);
            }

            $object->setPostPeriodDays($data['postPeriodDays']);
            $object->setBlogs(array_fill_keys($data['selectedBlogs'], 0));
            $object->setBlogTags(array_map('trim', explode(',', $data['blogTags'])));

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

            $status = array(
                CbCampaign::STATUS_READY,
                CbCampaign::STATUS_PROCESSING,
                CbCampaign::STATUS_COMPLETED,
                CbCampaign::STATUS_PAUSED
            );

            $arrayOfObjects = $model->getCampaignsByStatus($status);

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
                        'blogs' => $object->getBlogs(),
                        'blogTags' => $object->getBlogTags()
                    );

                    if($object->getType() == CbCampaign::TYPE_BACKLINKED){
                        $campaign['mainDomain'] = $object->getMainDomain();
                        $campaign['mainKeywords'] = $object->getMainKeywords();
                        $campaign['subLinks'] = $object->getSubLinks();
                        $campaign['additionalKeysPercentage'] = $object->getAdditionalKeysPercentage();
                        $campaign['postMainDomainLinks'] = $object->getPostMainDomainLinks();
                        $campaign['postSubLinks'] = $object->getPostSubLinks();
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

    /**
     * @Route("/campaign/remove", name="frontapi_campaign_remove")
     * @param Request $request
     * @Method("POST")
     * @return ApiResponse
     */
    public function removeCampaign(Request $request){

        $data = json_decode($request->getContent(), true);
        if(!isset($data['campaignId']) || !is_string($data['campaignId']) || strpos($data['campaignId'], 'campaign') === false) {
            return ApiResponse::resultNotFound();
        }

        try {
            $cb = $this->get('couchbase.connector');
            $model = new CampaignModel($cb);
            $campaignObject = $model->get($data['campaignId']);
            if ($campaignObject != null){
                $campaignObject->setStatus(CbCampaign::STATUS_DELETED);
                $model->upsert($campaignObject);
                return ApiResponse::resultValue(true);
            } else {
                return ApiResponse::resultNotFound();
            }
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}
