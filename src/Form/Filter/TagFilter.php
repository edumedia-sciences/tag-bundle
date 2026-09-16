<?php

namespace eduMedia\TagBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use eduMedia\TagBundle\Form\Type\TagFilterType;
use eduMedia\TagBundle\Service\TagService;
use Override;
use Symfony\Contracts\Translation\TranslatableInterface;

class TagFilter implements FilterInterface
{
    use FilterTrait;

    private TagService $tagService;

    public static function new(string $propertyName, string $taggableType, TagService $tagService, TranslatableInterface|string|bool|null $label = null): self
    {
        return new self()
            ->setFilterFqcn(self::class)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(TagFilterType::class)
            ->setFormTypeOption('taggable_type', $taggableType)
            ->setTagService($tagService);
    }

    public function setTagService(TagService $tagService): TagFilter
    {
        $this->tagService = $tagService;

        return $this;
    }

    #[Override]
    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $this->tagService->addTagFilterToQueryBuilder(
            $filterDataDto->getValue(),
            $filterDataDto->getFormTypeOption('taggable_type'),
            $queryBuilder,
            $filterDataDto->getEntityAlias(),
        );
    }
}
