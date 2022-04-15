<?php

namespace eduMedia\TagBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use eduMedia\TagBundle\Entity\TaggableInterface;
use eduMedia\TagBundle\Entity\TagInterface;

class TagService
{

    public function __construct(
        private EntityManagerInterface $manager,
        private string $tagClass = 'eduMedia\TagBundle\Entity\BaseTag',
        private string $taggingClass = 'eduMedia\TagBundle\Entity\BaseTagging'
    )
    {
    }

    public function addTag(TagInterface $tag, TaggableInterface $resource): void
    {
        $resource->getTags()->add($tag);
    }

    /**
     * @param TagInterface[] $tags
     * @param TaggableInterface $resource
     * @return void
     */
    public function addTags(array $tags, TaggableInterface $resource): void
    {
        foreach ($tags as $tag) {
            if ($tag instanceof TagInterface) {
                $this->addTag($tag, $resource);
            }
        }
    }

    public function removeTag(TagInterface $tag, TaggableInterface $resource): bool
    {
        return $resource->getTags()->removeElement($tag);
    }

    /**
     * @param TagInterface[] $tags
     * @param TaggableInterface $resource
     * @return void
     */
    public function replaceTags(array $tags, TaggableInterface $resource): void
    {
        $resource->getTags()->clear();
        $this->addTags($tags, $resource);
    }

    public function loadOrCreateTag($name): TagInterface
    {
        $tags = $this->loadOrCreateTags(array($name));
        return $tags[0];
    }

    /**
     * Loads or creates multiples tags from a list of tag names
     *
     * @param string[] $names Array of tag names
     * @return TagInterface[]
     */
    public function loadOrCreateTags(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        $names = array_unique($names);

        $builder = $this->manager->createQueryBuilder();

        $tags = $builder
            ->select('t')
            ->from($this->tagClass, 't')

            ->where($builder->expr()->in('t.name', $names))

            ->getQuery()
            ->getResult()
        ;

        $loadedNames = [];
        foreach ($tags as $tag) {
            $loadedNames[] = $tag->getName();
        }

        $missingNames = array_udiff($names, $loadedNames, 'strcasecmp');
        if (sizeof($missingNames)) {
            foreach ($missingNames as $name) {
                $tag = $this->createTag($name);
                $this->manager->persist($tag);

                $tags[] = $tag;
            }

            $this->manager->flush();
        }

        return $tags;
    }

    protected function createTag($name)
    {
        return new $this->tagClass($name);
    }

    protected function createTagging(TagInterface $tag, TaggableInterface $resource)
    {
        return new $this->taggingClass($tag, $resource);
    }

}