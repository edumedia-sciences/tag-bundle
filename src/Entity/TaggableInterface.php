<?php

namespace eduMedia\TagBundle\Entity;

interface TaggableInterface
{

    function getTaggableType(): ?string;
    function getTaggableId(): ?int;

}