<?php

namespace eduMedia\TagBundle\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use eduMedia\TagBundle\Entity\TaggableInterface;
use eduMedia\TagBundle\Service\TagService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{

    public function __construct(
        private TagService             $tagService,
        private EntityManagerInterface $entityManager,
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Replace choices and data options
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $resource = $this->getTaggable($event->getForm(), $options);
            $allTagNames = $this->tagService->getTypeTagNames($resource->getTaggableType());
            $entityTagNames = $this->tagService->getTagNames($resource, true);

            $fieldForm = $event->getForm();
            $parentForm = $fieldForm->getParent();

            $oldFieldForm = $parentForm->get($fieldForm->getName());
            if ($oldFieldForm->getConfig()->getOption('replaced')) {
                return;
            }

            $choices = array_combine($allTagNames, $allTagNames);

            $fieldOptions = $event->getForm()->getConfig()->getOptions();
            $fieldOptions = array_merge($fieldOptions, [
                'choices'  => $choices,
                'data'     => $entityTagNames,
                'replaced' => true, // To prevent an infinite replacement loop
            ]);

            // Re-add the field with the new choices
            $parentForm->add($fieldForm->getName(), self::class, $fieldOptions);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $taggable = $this->getTaggable($event->getForm(), $options);
            $tagNames = $event->getData();

            $fieldForm = $event->getForm();
            $parentForm = $fieldForm->getParent();

            // Allow any value, even created ones, by overriding choices
            $fieldOptions = $event->getForm()->getConfig()->getOptions();
            $fieldOptions['choices'] = array_combine($tagNames, $tagNames);
            $parentForm->add($fieldForm->getName(), self::class, $fieldOptions);

            // Acting on newly created entities
            if (is_null($taggable->getTaggableId())) {
                // We don't need to remove that listener, because persisting only happens once
                $this->entityManager->getEventManager()->addEventListener(Events::postPersist, function () use ($taggable, $tagNames) {
                    $this->tagService->replaceTags($this->tagService->loadOrCreateTags($tagNames), $taggable, true);
                });

                return;
            }

            $this->tagService->replaceTags($this->tagService->loadOrCreateTags($tagNames), $taggable, true);
        }, 1);
    }

    private function getTaggable(FormInterface $form, array $options): TaggableInterface
    {
        $taggable = $form->getParent()->getData();

        if (isset($options['entity_taggable_property'])) {
            $entityTaggableProperty = $options['entity_taggable_property'];
            $taggable = $taggable->$entityTaggableProperty;
        }

        return $taggable;
    }

    public function getParent(): string
    {
        return ChoiceType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped'                   => false,
            'entity_taggable_property' => null,
            'multiple'                 => true,
            'attr'                     => [
                'data-ea-widget'                         => 'ea-autocomplete',
                'data-ea-autocomplete-allow-item-create' => 'true',
            ],
            'replaced'                 => false,
        ]);
    }

}
