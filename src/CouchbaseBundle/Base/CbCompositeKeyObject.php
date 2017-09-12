<?php

namespace CouchbaseBundle\Base;

abstract class CbCompositeKeyObject extends CbBaseObject
{

    public function setObjectId($id)
    {
        $this->values['docId'] = $id;
    }

    public function getObjectId()
    {
        if (isset($this->values['docId'])) {
            return $this->values['docId'];
        }

        return null;
    }
}
