<?php

namespace eduMedia\TagBundle\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use eduMedia\TagBundle\Entity\TaggableInterface;
use eduMedia\TagBundle\Entity\TaggingInterface;
use eduMedia\TagBundle\Entity\TagInterface;

class TagService
{

    private Collection $entityTags;

    public function __construct(
        private string $tagClass,
        private string $taggingClass,
        private EntityManagerInterface $manager
    ) {
        $this->entityTags = new ArrayCollection();
    }

    public function loadOrCreateTag($name): TagInterface
    {
        $tags = $this->loadOrCreateTags([$name]);

        return $tags[0];
    }

    /**
     * @param string[] $names Array of tag names
     * @return TagInterface[]
     */
    public function loadOrCreateTags(array $names, bool $flush = true): array
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
            ->getResult();

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

            if ($flush) {
                $this->manager->flush();
            }
        }

        return $tags;
    }

    public function getTags(TaggableInterface $resource, bool $autoload = false): Collection
    {
        if ($autoload) {
            $this->loadTagging($resource);
        }

        $key = $this->getResourceKey($resource);

        if (!$this->entityTags->containsKey($key)) {
            $this->entityTags->set($key, new ArrayCollection());
        }

        return $this->entityTags->get($key);
    }

    public function addTag(TagInterface $tag, TaggableInterface $resource): self
    {
        $tags = $this->getTags($resource);
        if (!$tags->contains($tag)) {
            $tags->add($tag);
        }

        return $this;
    }

    /**
     * @param TagInterface[] $tags
     */
    public function addTags(array $tags, TaggableInterface $resource): self
    {
        foreach ($tags as $tag) {
            if ($tag instanceof TagInterface) {
                $this->addTag($tag, $resource);
            }
        }

        return $this;
    }

    public function removeTag(TagInterface $tag, TaggableInterface $resource): self
    {
        $this->getTags($resource)->removeElement($tag);

        return $this;
    }

    /**
     * @param TagInterface[] $tags
     */
    public function replaceTags(array $tags, TaggableInterface $resource, bool $andSaveTagging = false, bool $doNotFlush = false): self
    {
        $this->entityTags->remove($this->getResourceKey($resource));
        $this->addTags($tags, $resource);

        if ($andSaveTagging) {
            $this->saveTagging($resource, $doNotFlush);
        }

        return $this;
    }

    /**
     * @param TaggableInterface[] $resources
     * @param array<int, TagInterface[]> $mappings
     */
    private function replaceResourcesTags(array $resources, array $mappings): self
    {
        foreach ($resources as $resource) {
            $tags = $mappings[$resource->getTaggableId()] ?? [];
            $this->replaceTags($tags, $resource);
        }

        return $this;
    }

    public function loadTagging(TaggableInterface $resource): self
    {
        $tags = $this->queryTagging($resource);
        $this->replaceTags($tags, $resource);

        return $this;
    }

    /**
     * @param TaggableInterface[] $resources
     */
    public function loadResourcesTagging(array $resources): self
    {
        if (count($resources) == 0) {
            return $this;
        }

        $ids = array_map(fn ($resource) => $resource->getTaggableId(), $resources);
        $taggableType = $resources[0]->getTaggableType();

        $resourcesTagging = $this->queryResourcesTagging($ids, $taggableType);

        $mappings = [];
        foreach ($resourcesTagging as $tagging) {
            $mappings[$tagging->getResourceId()][] = $tagging->getTag();
        }

        $this->replaceResourcesTags($resources, $mappings);

        return $this;
    }

    public function saveTagging(TaggableInterface $resource, bool $doNotFlush = false): self
    {
        $oldTags = $this->queryTagging($resource);
        $newTags = $this->getTags($resource);
        $tagsToAdd = clone $newTags;

        if (!empty($oldTags)) {
            $tagsToRemove = array();

            foreach ($oldTags as $oldTag) {
                if ($newTags->exists(function ($index, $newTag) use ($oldTag) {
                    return $newTag->getName() == $oldTag->getName();
                })) {
                    $tagsToAdd->removeElement($oldTag);
                } else {
                    $tagsToRemove[] = $oldTag->getId();
                }
            }

            if (sizeof($tagsToRemove)) {
                $builder = $this->manager->createQueryBuilder();
                $builder
                    ->delete($this->taggingClass, 't')
                    ->where('t.tag_id')
                    ->where($builder->expr()->in('t.tag', $tagsToRemove))
                    ->andWhere('t.resourceType = :resourceType')
                    ->setParameter('resourceType', $resource->getTaggableType())
                    ->andWhere('t.resourceId = :resourceId')
                    ->setParameter('resourceId', $resource->getTaggableId())
                    ->getQuery()
                    ->getResult();
            }
        }

        foreach ($tagsToAdd as $tag) {
            $this->manager->persist($tag);
            $this->manager->persist($this->createTagging($tag, $resource));
        }

        if (count($tagsToAdd) && !$doNotFlush) {
            $this->manager->flush();
        }

        return $this;
    }

    public function deleteTagging(TaggableInterface $resource): self
    {
        $taggingList = $this->manager->createQueryBuilder()
            ->select('t')
            ->from($this->taggingClass, 't')
            ->where('t.resourceType = :type')
            ->setParameter('type', $resource->getTaggableType())
            ->andWhere('t.resourceId = :id')
            ->setParameter('id', $resource->getTaggableId())
            ->getQuery()
            ->getResult();

        foreach ($taggingList as $tagging) {
            $this->manager->remove($tagging);
        }

        return $this;
    }

    /* --- */

    /**
     * @return string[]
     */
    public function splitTagNames(string $names, string $separator = ','): array
    {
        $tags = explode($separator, $names);
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags, function ($value) {
            return !empty($value);
        });

        return array_values($tags);
    }

    /**
     * @return string[]
     */
    public function getTagNames(TaggableInterface $resource, bool $autoload = false): array
    {
        $names = [];

        $tags = $this->getTags($resource, $autoload);

        foreach ($tags as $tag) {
            $names[] = $tag->getName();
        }

        return $names;
    }

    public function getTagsWithCountArray(string $taggableType, ?int $limit = null, string $tagLookupField = 'name'): array
    {
        $qb = $this->getTagsWithCountArrayQueryBuilder($taggableType);

        if (null !== $limit) {
            $qb->setMaxResults($limit);
        }

        $tags = $qb->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR);

        $arr = array();
        foreach ($tags as $tag) {
            $count = $tag['tag_count'];

            // don't include orphaned tags
            if ($count > 0) {
                $tagName = $tag[$tagLookupField];
                $arr[$tagName] = $count;
            }
        }

        return $arr;
    }

    public function getTagsWithCountArrayQueryBuilder(string $taggableType, string $tagLookupField = 'name'): QueryBuilder
    {
        return $this->getTagsQueryBuilder($taggableType)
            ->groupBy('tagging.tag')
            ->select('tag.' . $tagLookupField . ', COUNT(tagging.tag) as tag_count')
            ->orderBy('tag_count', 'DESC');
    }

    public function getTagsQueryBuilder(string $taggableType): QueryBuilder
    {
        return $this->createQueryBuilder('tag')
            ->join('tag.tagging', 'tagging')
            ->where('tagging.resourceType = :resourceType')
            ->setParameter('resourceType', $taggableType);
    }

    /**
     * @return int[]
     */
    public function getResourceIdsForTag(string $taggableType, string $tagName, string $tagLookupField = 'name'): array
    {
        $results = $this->getTagsQueryBuilder($taggableType)
            ->andWhere('tag.' . $tagLookupField . ' = :tag')
            ->setParameter('tag', $tagName)
            ->select('tagging.resourceId')
            ->getQuery()
            ->execute(array(), AbstractQuery::HYDRATE_SCALAR);

        return array_map(fn($result) => (int)$result['resourceId'], $results);
    }

    /**
     * @return int[]
     */
    public function getResourceIdsForTags(string $taggableType, array $tagNames, string $tagLookupField = 'name'): array
    {
        $queryBuilder = $this->getTagsQueryBuilder($taggableType);
        $queryBuilder->andWhere($queryBuilder->expr()->in('tag.' . $tagLookupField, ':tags'))
            ->setParameter('tags', $tagNames)
            ->groupBy('tagging.resourceId')
            ->having('COUNT(tagging.resourceId) = :count')
            ->setParameter('count', count($tagNames))
            ->select('tagging.resourceId');

        $results = $queryBuilder->getQuery()->getResult();

        return array_map(fn($result) => (int)$result['resourceId'], $results);
    }

    protected function createQueryBuilder(string $alias, $indexBy = null): QueryBuilder
    {
        return $this->manager->createQueryBuilder()
            ->select($alias)
            ->from($this->tagClass, $alias, $indexBy);
    }

    protected function createTag($name): TagInterface
    {
        return new $this->tagClass($name);
    }

    private function getResourceKey(TaggableInterface $resource): string
    {
        return $resource->getTaggableType() . ':' . $resource->getTaggableId();
    }

    /**
     * @return TagInterface[]
     */
    protected function queryTagging(TaggableInterface $resource): array
    {
        return $this->manager
            ->createQueryBuilder()
            ->select('t')
            ->from($this->tagClass, 't')
            ->innerJoin('t.tagging', 't2', Expr\Join::WITH, 't2.resourceId = :id AND t2.resourceType = :type')
            ->setParameter('id', $resource->getTaggableId())
            ->setParameter('type', $resource->getTaggableType())

             ->orderBy('t.name', 'ASC')

            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $ids
     * @param string $taggableType
     * @return TaggingInterface[]
     */
    protected function queryResourcesTagging(array $ids, string $taggableType): array {

        if (count($ids) === 0) {
            return [];
        }

        return $this->manager
            ->createQueryBuilder()
            ->select('tagging')
            ->from($this->taggingClass, 'tagging')
            ->andWhere('tagging.resourceId IN (:ids)')
            ->andWhere('tagging.resourceType = :type')
            ->join('tagging.tag', 'tag')
            ->addSelect('tag')
            ->setParameter('ids', $ids)
            ->setParameter('type', $taggableType)

            ->getQuery()
            ->getResult();
    }

    public function getTypeTagNames(string $taggableType): array
    {
        $names = $this->manager
            ->createQueryBuilder()
            ->select('t.name')
            ->from($this->tagClass, 't')
            ->innerJoin('t.tagging', 't2', Expr\Join::WITH, 't2.resourceType = :type')
            ->setParameter('type', $taggableType)
            ->getQuery()
            ->getResult();

        return array_map(fn(array $line) => $line['name'], $names);
    }

    protected function createTagging(TagInterface $tag, TaggableInterface $resource): TaggingInterface
    {
        return new $this->taggingClass($tag, $resource);
    }

}