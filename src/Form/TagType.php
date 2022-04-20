<?php

namespace eduMedia\TagBundle\Form;

use eduMedia\TagBundle\Entity\TaggableInterface;
use eduMedia\TagBundle\Service\TagService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{

    public function __construct(private TagService $tagService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $childName = 'tags';

        $builder->add($childName, ChoiceType::class, [
            'multiple' => true,
            'attr'     => [
                'data-ea-widget' => 'ea-autocomplete',
                'data-ea-autocomplete-allow-item-create' => 'true',
            ],
        ])->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($childName) {
                $parentForm = $event->getForm()->getParent();
                /** @var TaggableInterface $taggable */
                $taggable = $parentForm->getData();
                // We retrieve the existing options to override some of them
                $options = $event->getForm()->get($childName)->getConfig()->getOptions();
                // We prefill options with the existing tags for this resource type
                $allTagNames = $this->tagService->getTypeTagNames($taggable->getTaggableType());
                // They are our new choices
                $options['choices'] = array_combine($allTagNames, $allTagNames);
                // We also need to select the entity's tags
                $options['data'] = $this->tagService->loadTagging($taggable)->getTagNames($taggable);
                // We override the form field
                $event->getForm()->add($childName, ChoiceType::class, $options);
            }
        )->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($childName) {
            // Get chosen tags
            $options = $event->getForm()->get($childName)->getConfig()->getOptions();
            $tagNames = $event->getData()[$childName];
            // Some might be new ones (not added in FormEvents::PRE_SET_DATA), we force the choices to what's been chosen
            $options['choices'] = array_combine($tagNames, $tagNames);
            $event->getForm()->add($childName, ChoiceType::class, $options);

            // We assign selected tags
            $parentForm = $event->getForm()->getParent();
            /** @var TaggableInterface $taggable */
            $taggable = $parentForm->getData();
            $tags = $this->tagService->loadOrCreateTags($tagNames);
            $this->tagService->replaceTags($tags, $taggable)->saveTagging($taggable);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
        ]);
    }

}