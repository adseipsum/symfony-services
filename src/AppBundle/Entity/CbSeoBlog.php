<?php

namespace AppBundle\Entity;

use Rbl\CouchbaseBundle\Base\CbBaseObject;


class CbSeoBlog extends CbBaseObject
{

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function __construct()
    {
        parent::__construct();
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function isGoogleCheck() : bool
    {
        return $this->get('check_google_first');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getPings() : array
    {
        return $this->getArrayElement('pings');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getAvailabilities() : array
    {
        return $this->getArrayElement('availabilities');
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getDomainExpirationDate() : string
    {
        $ret = $this->get('domain_expiration_date');
        if ($ret == null) {
            $ret = "";
        }
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getUrl() : string
    {
        $ret = $this->get('url');
        if ($ret == null) {
            $ret = "";
        }
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getSeo() : array
    {
        $ret = array(
            'maj_cf' => $this->get('seo_maj_cf'),
            'maj_tf' => $this->get('seo_maj_tf'),
            'moz_pa' => $this->get('seo_moz_pa'),
            'moz_da' => $this->get('seo_moz_da'),
            'moz_rank' => $this->get('seo_moz_rank'),
            'alexa_rank' => $this->get('seo_alexa_rank')
        );
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////

}
