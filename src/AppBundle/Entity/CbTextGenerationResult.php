<?php

namespace AppBundle\Entity;

use Rbl\CouchbaseBundle\Base\CbBaseObject;

class CbTextGenerationResult extends CbBaseObject
{

    public function __construct()
    {
        parent::__construct();
    }

    public function setText(string $text)
    {
        $this->set('text', $text);
    }

    public function getText() : string
    {
        return $this->get('text');
    }

}