<?php

namespace eduMedia\TagBundle\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use eduMedia\TagBundle\Form\Type\TagType;

class TagField implements FieldInterface
{
    use FieldTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return new self()
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(TagType::class)
            ->setTemplatePath('@eduMediaTag/fields/tag.html.twig');
    }

}
