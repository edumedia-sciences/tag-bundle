<?php

namespace eduMedia\TagBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface TaggableInterface
{

    function getTaggableType(): ?string;
    function getTaggableId(): ?int;
    function getTags(): Collection;

}