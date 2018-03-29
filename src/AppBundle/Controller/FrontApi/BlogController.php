<?php

namespace AppBundle\Controller\FrontApi;

use Rbl\CouchbaseBundle\Entity\CbBlog;
use Rbl\CouchbaseBundle\Entity\CbSeoBlog;
use AppBundle\Extension\ApiResponse;
use Rbl\CouchbaseBundle\Model\BlogModel;
use Rbl\CouchbaseBundle\Model\SeoBlogModel;
use Rbl\CouchbaseBundle\CouchbaseService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class BlogController extends Controller
{
    /**
     * @Route("/blog/upsert", name="frontapi_blog_upsert")
     * @param Request $request
     * @Method("POST")
     * @return ApiResponse
     */
    public function upsertBlog(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        try {
            /* @var $cb CouchbaseService */
            $cb = $this->get('couchbase.connector');
            $model = new BlogModel($cb);

            $object = new CbBlog();
            $object->setRecordCreated();
            $object->setEnabled(true);
            $object->setLocked(false);

            if(isset($data['blogId']) && $data['blogId']) {
                $object = $model->get($data['blogId']);
                if(!$object){
                    return ApiResponse::resultNotFound();
                }
                $object->setRecordUpdated();
            }

            $object->setDomainName($data['domainName']);
            $object->setRealIp($data['realIp']);
            $object->setPostingUserLogin(isset($data['postingUserLogin']) ? $data['postingUserLogin'] : $object->getPostingUserLogin());
            $object->setPostingUserPassword(isset($data['postingUserPassword']) ? $data['postingUserPassword'] : $object->getPostingUserPassword());
            $object->setClientId(isset($data['clientId']) ? $data['clientId'] : $object->getClientId());
            $object->setClientSecret(isset($data['clientSecret']) ? $data['clientSecret'] : $object->getClientSecret());
            $object->setPostPeriodSeconds($data['postPeriodSeconds']);
            $object->setTags($this->multipleExplode(array(",",".","|",":"), $data['tags']));

            $model->upsert($object);

            return ApiResponse::resultValues(true);
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route("/blog/list", name="frontapi_blog_list")
     * @Method("GET")
     * @param Request $request
     * @return ApiResponse
     */

    public function getBlogList(Request $request)
    {

        $tags = $request->query->get('tags');

        /* @var $cb CouchbaseService */
        $cb = $this->get('couchbase.connector');
        $model = new BlogModel($cb);

        $seoModel = new SeoBlogModel($cb);

        try {

            /* @var $arrayOfObjects CbBlog[] */
            if(!$tags){
                $arrayOfObjects = $model->getAllObjects();
            }else{
                $arrayOfObjects = $model->getBlogListByTags($tags);
            }

            if ($arrayOfObjects != null){

                $ret = [];
                foreach($arrayOfObjects as $object) {

                    $id = $object->getObjectId();

                    $blog = array(
                        'id' => $id,
                        'enabled' => $object->getEnabled(),
                        'locked' => $object->getLocked(),
                        'domainName' => $object->getDomainName(),
                        'realIp' => $object->getRealIp(),
                        'postPeriodSeconds' => $object->getPostPeriodSeconds(),
                        'tags' => $object->getTags(),
                        'lastPostDate' => $object->getLastPostDate() ? $object->getLastPostDate()->getTimestamp() : -1,
                        'lastPostId' => $object->getLastPostId(),
                        'lastBacklinkedPostId' => $object->getLastBacklinkedPostId(),
                    );

                    /* @var $seoBlogDataObject CbSeoBlog */
                    $seoBlogDataObject = $seoModel->get('seo-' . $id);

                    if($seoBlogDataObject){
                        $pings = $seoBlogDataObject->getPings();
                        $pingsCountAll = 0;
                        $pingsCountValid = 0;
                        foreach($pings as $ping) {
                            $pingsCountAll++;
                            $status = $ping['status'];
                            if ($status == 1) {
                                $pingsCountValid++;
                            }
                        }

                        $availabilities = $seoBlogDataObject->getAvailabilities();
                        $availabilitiesCountAll = 0;
                        $availabilitiesCountValid = 0;
                        foreach($availabilities as $availabilitie) {
                            $availabilitiesCountAll++;
                            $status = $availabilitie['status'];
                            if ($status == 1) {
                                $availabilitiesCountValid++;
                            }
                        }

                        $expirationDate = strtotime($seoBlogDataObject->getDomainExpirationDate());
                        if (!$expirationDate) {
                            $expirationDate = -1;
                        }

                        $seoData = array(
                            'isGoogleCheck' => $seoBlogDataObject->isGoogleCheck(),
                            'pingsCountAll' => $pingsCountAll,
                            'pingsCountValid' => $pingsCountValid,
                            'availabilitiesCountAll' => $availabilitiesCountAll,
                            'availabilitiesCountValid' => $availabilitiesCountValid,
                            'domainExpirationDate' => $expirationDate,
                            'url' => $seoBlogDataObject->getUrl(),
                            'googleFirstUrl' => $seoBlogDataObject->getGoogleFirstUrl(),
                            'urlIndex' => $seoBlogDataObject->getUrlIndex(),
                            'isCheckGoogle' => $seoBlogDataObject->isCheckGoogle(),
                            'seo' => $seoBlogDataObject->getSeo(),
                            'checkTimestamp' => $seoBlogDataObject->getCheckTimestamp(),
                            'seoCheckTimestamp' => $seoBlogDataObject->getSeoCheckTimestamp(),
                        );
                        $blog = array_merge($blog, $seoData);
                    }

                    $ret[] = $blog;
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
     * @Route("blog/seo/pings/{blogId}", name="frontapi_blog_seo_pings", requirements={"template": "[a-zA-Z0-9_\-]+"})
     * @method ("GET")
     * @param Request $request
     * @return ApiResponse
     */
    public function getBlogSeoPings(Request $request, string $blogId)
    {
        /* @var $cb CouchbaseService */
        $cb = $this->get('couchbase.connector');
        $seoModel = new SeoBlogModel($cb);

        try {
            /* @var $seoBlogDataObject CbSeoBlog */
            $seoBlogDataObject = $seoModel->get('seo-' . $blogId);

            if($seoBlogDataObject){
                $ret = $seoBlogDataObject->getPings();
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
     * @Route("blog/seo/availabilities/{blogId}", name="frontapi_blog_seo_availabilities", requirements={"template": "[a-zA-Z0-9_\-]+"})
     * @method ("GET")
     * @param Request $request
     * @return ApiResponse
     */
    public function getBlogSeoAvailabilities(Request $request, string $blogId)
    {
        /* @var $cb CouchbaseService */
        $cb = $this->get('couchbase.connector');
        $seoModel = new SeoBlogModel($cb);

        try {
            /* @var $seoBlogDataObject CbSeoBlog */
            $seoBlogDataObject = $seoModel->get('seo-' . $blogId);

            if($seoBlogDataObject){
                $ret = $seoBlogDataObject->getAvailabilities();
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
     * @Route("/blog/tags", name="frontapi_blog_tags")
     * @Method("GET")
     * @return ApiResponse
     */

    public function getBlogTags()
    {
        try {
            /* @var $cb CouchbaseService */
            $cb = $this->get('couchbase.connector');
            $model = new BlogModel($cb);

            $arrayOfObjects = $model->getBlogTags();

            if ($arrayOfObjects != null){
                return ApiResponse::resultValue($arrayOfObjects);
            } else {
                return ApiResponse::resultNotFound();
            }
        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @Route("/blog/lock", name="frontapi_block_lock")
     * @param Request $request
     * @Method("GET")
     * @return ApiResponse
     */
    public function lockBlog(Request $request)
    {
        $data = $request->query->all();

        if(!isset($data['locked']) || !isset($data['blogId'])){
            return ApiResponse::resultNotFound();
        }

        try {
            /* @var $cb CouchbaseService */
            $cb = $this->get('couchbase.connector');
            $model = new BlogModel($cb);
            $blogObject = $model->get($data['blogId']);
            $blogObject->setLocked(filter_var($data['locked'], FILTER_VALIDATE_BOOLEAN));
            $model->upsert($blogObject);

        } catch (Exception $e) {
            return ApiResponse::resultError(500, $e->getMessage());
        }

        return ApiResponse::resultValue(true);

    }


    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function multipleExplode($delimiters, $string) {
        $ready = str_replace($delimiters, $delimiters[0], $string);
        $exploded = explode($delimiters[0], $ready);
        $trimmed = array_map('trim', $exploded);
        return  $trimmed;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}
