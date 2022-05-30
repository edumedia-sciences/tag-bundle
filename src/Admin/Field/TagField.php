<?php

namespace eduMedia\TagBundle\Admin\Field;

use EasyCorp\Bundle\EasyAdminBundle\Config\Asset;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\FieldTrait;
use eduMedia\TagBundle\Form\Type\TagType;

class TagField implements FieldInterface
{

    use FieldTrait;

    public static function new(string $propertyName, ?string $label = null)
    {
        return (new self())
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(TagType::class)
            ->addCssClass('field-text field-edumedia-tag')
            ->setDefaultColumns('col-md-6 col-xxl-5')
            // TODO: Improve
            ->addHtmlContentsToHead('<script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>')
            ->addJsFiles(Asset::new('bundles/edumediatag/tag-field.js')->onlyOnForms())
            ->setTemplatePath('@eduMediaTag/fields/tag.html.twig')
            ;
    }

}