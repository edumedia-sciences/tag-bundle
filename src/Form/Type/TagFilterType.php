<?php

namespace eduMedia\TagBundle\Form\Type;

use EasyCorp\Bundle\EasyAdminBundle\Form\Type\ComparisonType;
use eduMedia\TagBundle\Service\TagService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagFilterType extends AbstractType
{

    public function __construct(
        private TagService $tagService,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Hidden field, to allow EA filters to work
        // If we use a single/simple ChoiceType, EA breaks with `Warning: Undefined array key "comparison"
        $builder->add('comparison', HiddenType::class, [
            'empty_data' => ComparisonType::EQ,
        ]);

        $builder->add('value', ChoiceType::class, [
            'multiple' => true,
            'attr'     => [
                'data-ea-widget' => 'ea-autocomplete',
            ],
        ]);

        // Replace choice options
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $allTagNames = $this->tagService->getTypeTagNames($options['taggable_type']);
            $choices = array_combine($allTagNames, $allTagNames);

            $fieldOptions = $event->getForm()->get('value')->getConfig()->getOptions();
            $fieldOptions = array_merge($fieldOptions, [
                'choices' => $choices,
            ]);

            // Re-add the field with the new choices
            $event->getForm()->add('value', ChoiceType::class, $fieldOptions);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped'        => false,
            'taggable_type' => null,
        ]);
    }

}
