<?php

namespace AppBundle\Controller\FrontApi;

use AppBundle\Entity\CbBlog;
use AppBundle\Extension\ApiResponse;
use AppBundle\Repository\BlogModel;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;

class BlogController extends Controller
{
    /**
     * @Route("/blog/new", name="frontapi_blog_new")
     * @param Request $request
     * @Method("POST")
     * @return ApiResponse
     */
    public function addNewBlog(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        try {
            $cb = $this->get('couchbase.connector');
            $model = new BlogModel($cb);

            $object = new CbBlog();
            $object->setEnabled(true);
            $object->setLocked(false);
            $object->setDomainName($data['domainName']);
            $object->setPostingUserLogin($data['postingUserLogin']);
            $object->setPostingUserPassword($data['postingUserPassword']);
            $object->setClientId($data['clientId']);
            $object->setClientSecret($data['clientSecret']);
            $object->setPostPeriodSeconds($data['postPeriodSeconds']);
            $object->setTags($this->multipleExplode(array(",",".","|",":"), $data['tags']));
            $object->setRecordCreated();

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

        $cb = $this->get('couchbase.connector');
        $model = new BlogModel($cb);

        try {

            if(!$tags){
                $arrayOfObjects = $model->getAllObjects();
            }else{
                $arrayOfObjects = $model->getBlogListByTags($tags);
            }

            if ($arrayOfObjects != null){

                $ret = [];
                foreach($arrayOfObjects as $object) {

                    $ret[] = array(
                        'id' => $object->getObjectId(),
                        'enabled' => $object->getEnabled(),
                        'domainName' => $object->getDomainName(),
                        'postingUserLogin' => $object->getPostingUserLogin(),
                        'postingUserPassword' => $object->getPostingUserPassword(),
                        'clientId' => $object->getClientId(),
                        'clientSecret' => $object->getClientSecret(),
                        'postPeriodSeconds' => $object->getPostPeriodSeconds(),
                        'tags' => $object->getTags(),
                        'lastPostDate' => $object->getLastPostDate()->format('d-m-Y h:i:s')
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

    /**
     * @Route("/blog/tags", name="frontapi_blog_tags")
     * @Method("GET")
     * @return ApiResponse
     */

    public function getBlogTags()
    {
        try {
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

    private function multipleExplode($delimiters, $string) {

        $ready = str_replace($delimiters, $delimiters[0], $string);
        $exploded = explode($delimiters[0], $ready);
        $trimmed = array_map('trim', $exploded);
        return  $trimmed;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}