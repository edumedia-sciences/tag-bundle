<?php

namespace eduMedia\TagBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class eduMediaTagBundle extends Bundle
{

    /**
     * This is the parent's method, without the naming convention test
     * Because we use a non-conventional alias in the bundle's Extension
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $extension = $this->createContainerExtension();

            if (null !== $extension) {
                if (!$extension instanceof ExtensionInterface) {
                    throw new \LogicException(sprintf('Extension "%s" must implement Symfony\Component\DependencyInjection\Extension\ExtensionInterface.', get_debug_type($extension)));
                }

//                // check naming convention
//                $basename = preg_replace('/Bundle$/', '', $this->getName());
//                $expectedAlias = Container::underscore($basename);
//
//                if ($expectedAlias != $extension->getAlias()) {
//                    throw new \LogicException(sprintf('Users will expect the alias of the default extension of a bundle to be the underscored version of the bundle name ("%s"). You can override "Bundle::getContainerExtension()" if you want to use "%s" or another alias.', $expectedAlias, $extension->getAlias()));
//                }

                $this->extension = $extension;
            } else {
                $this->extension = false;
            }
        }

        return $this->extension ?: null;
    }

}