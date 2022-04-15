<?php

namespace eduMedia\TagBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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

    /**
     * @internal Use TagManager instead
     */
    public function getTags(): Collection {
        if (is_null($this->tags)) {
            $this->tags = new ArrayCollection();
        }

        return $this->tags;
    }

}