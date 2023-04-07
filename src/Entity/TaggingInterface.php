<?php

namespace eduMedia\TagBundle\Entity;

interface TaggingInterface
{

    function getResourceType(): ?string;
    function setResourceType(string $type): self;
    function getResourceId(): ?int;
    function setResourceId(int $id): self;

    function getTag(): TagInterface;
    function setTag(TagInterface $tag): self;

}