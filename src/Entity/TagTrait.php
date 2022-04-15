<?php

namespace eduMedia\TagBundle\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

trait TagTrait
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: 'eduMedia\TagBundle\Entity\BaseTagging', fetch: 'EAGER')]
    protected ?Collection $tagging = null;

    #[ORM\Column(type: 'string', unique: true)]
    private string $name;

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

//    public function getTagging(): ?Collection
//    {
//        return $this->tagging;
//    }
//
//    public function setTagging(Collection $tagging): self
//    {
//        $this->tagging = $tagging;
//
//        return $this;
//    }

}