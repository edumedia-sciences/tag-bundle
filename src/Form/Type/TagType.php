<?php

namespace eduMedia\TagBundle\Form\Type;

use eduMedia\TagBundle\Entity\TaggableInterface;
use eduMedia\TagBundle\Service\TagService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagType extends AbstractType
{

    public function __construct(private TagService $tagService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {
            $resource = $this->getTaggable($event->getForm(), $options);
            $tagNames = $this->tagService->loadTagging($resource)->getTagNames($resource);
            $event->setData(implode(', ', $tagNames));
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $taggable = $this->getTaggable($event->getForm(), $options);

            $tagNames = array_filter(
                array_map(function (string $s) {
                    return trim($s);
                }, explode(',', $event->getData()))
            );

            $tags = $this->tagService->loadOrCreateTags($tagNames);
            $this->tagService->replaceTags($tags, $taggable)->saveTagging($taggable);
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $taggable = $this->getTaggable($form, $options);

        $view->vars = array_replace($view->vars, [
            'attr' => [
                'data-suggestions' => json_encode($this->tagService->getTypeTagNames($taggable->getTaggableType())),
                'data-values'      => json_encode($this->tagService->loadTagging($taggable)->getTagNames($taggable)),
            ],
        ]);
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
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped'                   => false,
            'entity_taggable_property' => null,
        ]);
    }

}