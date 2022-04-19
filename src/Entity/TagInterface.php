<?php

namespace eduMedia\TagBundle\Entity;

interface TagInterface
{

    function getId();

    function getName(): ?string;

}