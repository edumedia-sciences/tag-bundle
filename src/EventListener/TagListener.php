<?php

namespace eduMedia\TagBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use eduMedia\TagBundle\Entity\TaggableInterface;
use eduMedia\TagBundle\Service\TagService;

class TagListener
{

    public function __construct(private TagService $tagService)
    {
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        if (($resource = $args->getEntity()) and $resource instanceof TaggableInterface) {
            $this->tagService->deleteTagging($resource);
        }
    }

}