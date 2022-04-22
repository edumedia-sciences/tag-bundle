# eduMedia Tag Bundle

## Rationale

Revival of https://github.com/FabienPennequin/FPNTagBundle for Symfony 6+ and PHP 8+.

**Note:** The main difference with `fpn/tag-bundle` is that taggable entities do not hold their tags, they're only available through the `TagService`.

## How to

### Install bundle

```sh
composer require edumedia/tag-bundle
```

### Create Tag class

```php
<?php
// src/Entity/Tag.php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use eduMedia\TagBundle\Entity\TagInterface;
use eduMedia\TagBundle\Entity\TagTrait;

#[ORM\Entity]
#[ORM\Table(name: 'tag')]
class Tag implements TagInterface
{

    use TagTrait;

    #[ORM\OneToMany(mappedBy: 'tag', targetEntity: 'App\Entity\Tagging', fetch: 'EAGER')]
    protected ?Collection $tagging = null;

}
```

### Create Tagging class

```php
<?php
// src/Entity/Tagging.php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use eduMedia\TagBundle\Entity\TaggingInterface;
use eduMedia\TagBundle\Entity\TaggingTrait;
use eduMedia\TagBundle\Entity\TagInterface;

#[ORM\Entity]
#[ORM\Table(name: 'tagging')]
class Tagging implements TaggingInterface
{

    use TaggingTrait;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Tag', inversedBy: 'tagging')]
    protected TagInterface $tag;

}
```

### Make entity taggable

Here is a User example:

```php
<?php
// src/Entity/User

namespace App\Entity;

use eduMedia\TagBundle\Entity\TaggableInterface;
use eduMedia\TagBundle\Entity\TaggableTrait;

class User implements /* (...) */ TaggableInterface
{

    use TaggableTrait;
    
    // (...)
}
```

### Define service arguments

```yaml
# config/services.yaml
services:
  # (...)
  eduMedia\TagBundle\Service\TagService:
    arguments:
      - 'App\Entity\Tag'
      - 'App\Entity\Tagging'
```

### Migrate, to create tables

```sh
bin/console make:migration
bin/console doctrine:migrations:migrate
```

## Features

- Most features are packed in `eduMedia\TagBundle\Service\TagService` (not documented yet, but should be self-explanatory)
- If `symfony/console` is installed, a `edumedia:tag:create` command is available
- If `symfony/twig-bundle` is installed, `tag_service` is globally available
- If `symfony/form` is installed, `eduMedia\TagBundle\Form\Type\TagType` is available
- If using `easycorp/easyadmin-bundle`, a `eduMedia\TagBundle\Admin\Field\TagField` is available

## TODO

- Automated tests (help appreciated)
