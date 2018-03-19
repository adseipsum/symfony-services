<?php
/**
 * Created by PhpStorm.
 * User: void
 * Date: 11/22/17
 * Time: 5:44 PM
 */

namespace AppBundle\Extension;

use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class JSonBody
{
    private $body;


    public function __construct(Request $request)
    {
        $this->body = json_decode($request->getContent(), true);
    }

    public function get($name, $default=null)
    {
        if(isset($this->body[$name]))
        {
            return $this->body[$name];
        }
        else {
            return $default;
        }
    }

    public function getReq($name, $default=null)
    {
        if(isset($this->body[$name]))
        {
            return $this->body[$name];
        }
        else {
            throw new InvalidArgumentException("Parameter [$name] not set in json request");
        }
    }
}