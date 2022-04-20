<?php

namespace eduMedia\TagBundle\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use eduMedia\TagBundle\Form\Type\TagType;

class TagField implements FieldInterface
{

    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null)
    {
        return (new self())
            ->setProperty('tags')
            ->setLabel(false)
            ->setFormType(TagType::class)
            ->setTemplatePath('@eduMediaTag/fields/tag.html.twig')
            ;
    }

}