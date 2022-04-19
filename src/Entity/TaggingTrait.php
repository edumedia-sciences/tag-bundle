<?php

namespace eduMedia\TagBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

trait TaggingTrait
{

//    #[ORM\Id]
//    #[ORM\ManyToOne(targetEntity: 'eduMedia\TagBundle\Entity\AbstractTag', inversedBy: 'tagging')]
    protected TagInterface $tag;

    #[ORM\Id]
    #[ORM\Column(type: 'string')]
    private ?string $resourceType = null;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    private ?int $resourceId = null;

    public function __construct(TagInterface $tag = null, TaggableInterface $resource = null)
    {
        if ($tag != null) {
            $this->setTag($tag);
        }

        if ($resource != null) {
            $this->setResource($resource);
        }
    }

    public function getTag(): TagInterface
    {
        return $this->tag;
    }

    public function setTag(TagInterface $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getResourceType(): ?string
    {
        return $this->resourceType;
    }

    public function setResourceType(string $resourceType): self
    {
        $this->resourceType = $resourceType;

        return $this;
    }

    public function getResourceId(): ?int
    {
        return $this->resourceId;
    }

    public function setResourceId(int $resourceId): self
    {
        $this->resourceId = $resourceId;

        return $this;
    }

    public function setResource(TaggableInterface $resource): self
    {
        $this->resourceType = $resource->getTaggableType();
        $this->resourceId = $resource->getTaggableId();

        return $this;
    }

}