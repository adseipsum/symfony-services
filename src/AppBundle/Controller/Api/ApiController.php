<?php
/**
 * Created by PhpStorm.
 * User: void
 * Date: 9/15/17
 * Time: 4:14 PM
 */

namespace AppBundle\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ApiController extends Controller
{
    public function getParameter($name)
    {
        return parent::getParameter($name);
    }
}