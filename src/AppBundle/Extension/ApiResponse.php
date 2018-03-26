<?php

namespace AppBundle\Extension;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponse extends JsonResponse
{

    public function __construct($data = null, $status = 200, $headers = array())
    {
        parent::__construct($data, $status, $headers);
        //ApiResponse::applyCORS($this);
    }

    public static function resultError($status, $message, $ttl = 0)
    {
        $data['status']['code'] = $status;
        $data['status']['message'] = $message;

        $response = new ApiResponse($data, $status);
        $response->setPublic();

        if ($ttl != 0) {
            $response->setTtl($ttl);
        }

        return $response;
    }

    /**
     * добавляет в ответ Cross Origin Resource Sharing (CORS)
     *
     * @param ApiResponse $response - ответ сервера
     */
    protected static function applyCORS(ApiResponse $response)
    {
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, PATCH, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, Content-Type, Accept, Authorization');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Vary', 'Origin');
        $response->headers->set('Access-Control-Max-Age', 3600);
    }

    public static function resultException(\Exception $exception)
    {
        $data['status']['code'] = 500;
        $data['status']['message'] = $exception->getMessage();

        $response = new ApiResponse($data, 500);
        $response->setPublic();

        return $response;
    }

    public static function resultValues($values, $status = 200, $message = null, $ttl = 0)
    {
        $data['status']['code'] = $status;
        if ($message != null) {
            $data['status']['message'] = $message;
        }
        $data['result']['count'] = count($values);
        $data['result']['values'] = $values;

        $response = new ApiResponse($data, $status);
        $response->setPublic();

        if ($ttl != 0) {
            $response->setTtl($ttl);
        }

        return $response;
    }

    public static function resultValue($value, $status = 200, $message = null, $ttl = 0)
    {
        $data['status']['code'] = $status;
        if ($message != null) {
            $data['status']['message'] = $message;
        }

        $data['result']['value'] = $value;

        $response = new ApiResponse($data, $status);
        $response->setPublic();

        if ($ttl != 0) {
            $response->setTtl($ttl);
        }

        return $response;
    }

    public static function resultRepeat($status = 204, $message = null)
    {
        $data['status']['code'] = $status;
        if ($message != null) {
            $data['status']['message'] = $message;
        }

        $response = new ApiResponse($data, $status);
        $response->setPublic();

        return $response;
    }

    public static function resultOk($status = 200, $message = null)
    {
        $data['status']['code'] = $status;
        if ($message != null) {
            $data['status']['message'] = $message;
        }

        $response = new JsonResponse($data, $status);
        $response->setPublic();

        return $response;
    }

    public static function resultStatus($status = 200, $httpStatus = 200, $message = null)
    {
        $data['status']['code'] = $status;
        if ($message != null) {
            $data['status']['message'] = $message;
        }

        $response = new ApiResponse($data, $httpStatus);
        $response->setPublic();

        return $response;
    }

    public static function resultBadRequest($status = 400, $message = null)
    {
        $data['status']['code'] = $status;
        if ($message != null) {
            $data['status']['message'] = $message;
        }

        $response = new ApiResponse($data, $status);
        $response->setPublic();

        return $response;
    }

    public static function resultUnauthorized()
    {
        $data['status']['code'] = 401;
        $data['status']['message'] = 'unauthorized';

        $response = new ApiResponse($data, 401);
        $response->setPublic();

        return $response;
    }

    public static function resultNotFound($httpStatus = 404)
    {
        $data['status']['code'] = 404;
        $data['status']['message'] = 'Object not found';

        $response = new ApiResponse($data, $httpStatus);
        $response->setPublic();

        return $response;
    }
}
