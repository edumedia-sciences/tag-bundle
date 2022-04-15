<?php

namespace eduMedia\TagBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'tag')]
class BaseTag implements TagInterface
{

    use TagTrait;

    public function __construct()
    {
        $this->tagging = new ArrayCollection();
    }
}