<?php

namespace eduMedia\TagBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tagging')]
class BaseTagging implements TaggingInterface
{
    use TaggingTrait;

    public function __construct(TagInterface $tag = null, TaggableInterface $resource = null)
    {
        if ($tag != null) {
            $this->setTag($tag);
        }

        if ($resource != null) {
            $this->setResource($resource);
        }
    }

}