<?php

namespace eduMedia\TagBundle\Twig;

use eduMedia\TagBundle\Service\TagService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class TagExtension extends AbstractExtension implements GlobalsInterface
{

    public function __construct(private TagService $tagService)
    {
    }

    public function getGlobals(): array
    {
        return [
            'tag_service' => $this->tagService,
        ];
    }

}