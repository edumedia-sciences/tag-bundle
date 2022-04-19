<?php

namespace eduMedia\TagBundle\Entity;

use Doctrine\Common\Collections\Collection;

trait TaggableTrait
{

    private ?Collection $tags = null;

    public function getTaggableType():string {
        return self::class;
    }

    public function getTaggableId(): int {
        return $this->getId();
    }

}