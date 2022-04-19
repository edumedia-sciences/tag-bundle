<?php

namespace eduMedia\TagBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait TagTrait
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

//    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: 'eduMedia\TagBundle\Entity\AbstractTagging', fetch: 'EAGER')]
    protected ?Collection $tagging = null;

    #[ORM\Column(type: 'string', unique: true)]
    private string $name;

    public function __construct(?string $name = null)
    {
        $this->tagging = new ArrayCollection();
        $this->setName($name);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name = null): self
    {
        $this->name = $name;

        return $this;
    }

}