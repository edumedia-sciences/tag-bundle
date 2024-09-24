<?php

namespace eduMedia\TagBundle\EventListener;

use Doctrine\ORM\Event\PreRemoveEventArgs;
use eduMedia\TagBundle\Entity\TaggableInterface;
use eduMedia\TagBundle\Service\TagService;

class TagListener
{

    public function __construct(private TagService $tagService)
    {
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        if (($resource = $args->getObject()) and $resource instanceof TaggableInterface) {
            $this->tagService->deleteTagging($resource);
        }
    }

}