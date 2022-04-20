<?php

namespace eduMedia\TagBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class eduMediaTagExtension extends Extension
{

    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');

        if (class_exists('Symfony\Component\Console\Command\Command')) {
            $loader->load('console-command.yaml');
        }

        if (class_exists('Twig\Extension\AbstractExtension')) {
            $loader->load('twig-extension.yaml');
        }

//        if (class_exists('Symfony\Component\Form\AbstractType')) {
//            $loader->load('form-type.yaml');
//        }
    }

    public function getAlias(): string
    {
        return 'edumedia_tag';
    }

}