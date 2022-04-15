<?php

namespace eduMedia\TagBundle\Service;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use eduMedia\TagBundle\Entity\TaggableInterface;
use eduMedia\TagBundle\Entity\TaggingInterface;
use eduMedia\TagBundle\Entity\TagInterface;

class TagService
{

    /**
     * The field that's considered the "lookup" for tags
     *
     * @var string
     */
    protected string $tagLookupField = 'name';

    public function __construct(
        private EntityManagerInterface $manager,
        private string $tagClass = 'eduMedia\TagBundle\Entity\BaseTag',
        private string $taggingClass = 'eduMedia\TagBundle\Entity\BaseTagging'
    ) {
    }

    public function removeTag(TagInterface $tag, TaggableInterface $resource): self
    {
        $resource->getTags()->removeElement($tag);

        return $this;
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

            $this->manager->flush();
        }

        return $tags;
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

    public function saveTagging(TaggableInterface $resource): self
    {
        $oldTags = $this->getTagging($resource);
        $newTags = $resource->getTags();
        $tagsToAdd = $newTags;

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

        if (count($tagsToAdd)) {
            $this->manager->flush();
        }

        return $this;
    }

    /**
     * @return TagInterface[]
     */
    protected function getTagging(TaggableInterface $resource): array
    {
        return $this->manager
            ->createQueryBuilder()
            ->select('t')
            ->from($this->tagClass, 't')
            ->innerJoin('t.tagging', 't2', Expr\Join::WITH, 't2.resourceId = :id AND t2.resourceType = :type')
            ->setParameter('id', $resource->getTaggableId())
            ->setParameter('type', $resource->getTaggableType())

            // ->orderBy('t.name', 'ASC')

            ->getQuery()
            ->getResult();
    }

    protected function createTagging(TagInterface $tag, TaggableInterface $resource): TaggingInterface
    {
        return new $this->taggingClass($tag, $resource);
    }

    public function loadTagging(TaggableInterface $resource): self
    {
        $tags = $this->getTagging($resource);
        $this->replaceTags($tags, $resource);

        return $this;
    }

    /**
     * @param TagInterface[] $tags
     */
    public function replaceTags(array $tags, TaggableInterface $resource): self
    {
        $resource->getTags()->clear();
        $this->addTags($tags, $resource);

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

    public function addTag(TagInterface $tag, TaggableInterface $resource): self
    {
        if (!$resource->getTags()->contains($tag)) {
            $resource->getTags()->add($tag);
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
    public function getTagNames(TaggableInterface $resource): array
    {
        $names = array();

        if (sizeof($resource->getTags()) > 0) {
            foreach ($resource->getTags() as $tag) {
                $names[] = $tag->getName();
            }
        }

        return $names;
    }

    public function getTagsWithCountArray(string $taggableType, ?int $limit = null): array
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
                $tagName = $tag[$this->tagLookupField];
                $arr[$tagName] = $count;
            }
        }

        return $arr;
    }

    public function getTagsWithCountArrayQueryBuilder(string $taggableType): QueryBuilder
    {
        return $this->getTagsQueryBuilder($taggableType)
            ->groupBy('tagging.tag')
            ->select('tag.' . $this->tagLookupField . ', COUNT(tagging.tag) as tag_count')
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
    public function getResourceIdsForTag(string $taggableType, string $tagName): array
    {
        $results = $this->getTagsQueryBuilder($taggableType)
            ->andWhere('tag.' . $this->tagLookupField . ' = :tag')
            ->setParameter('tag', $tagName)
            ->select('tagging.resourceId')
            ->getQuery()
            ->execute(array(), AbstractQuery::HYDRATE_SCALAR);

        $ids = array();
        foreach ($results as $result) {
            $ids[] = (int)$result['resourceId'];
        }

        return $ids;
    }

}