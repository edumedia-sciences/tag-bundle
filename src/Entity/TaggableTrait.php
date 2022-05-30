<?php

namespace eduMedia\TagBundle\Entity;

trait TaggableTrait
{

    public function getTaggableType():string {
        return self::class;
    }

    public function getTaggableId(): ?int {
        return $this->getId();
    }

}