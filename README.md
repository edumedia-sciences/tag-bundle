# eduMedia Tag Bundle

## Rationale

Revival of https://github.com/FabienPennequin/FPNTagBundle for Symfony 6+ and PHP 8+.

## How to

### Install bundle

### Create Tag class

```php
// src/Entity/Tag.php
<?php

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
// src/Entity/Tagging.php
<?php

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
// src/Entity/User
<?php

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
services:
  # (...)
  eduMedia\TagBundle\Service\TagService:
    arguments:
      - 'App\Entity\Tag'
      - 'App\Entity\Tagging'
```

### Migrate

```sh
bin/console make:miggration
bin/console doctrine:migrations:migrate
```

## Changes

- Merged `TagManager` and `TagRepository` in a `TagService`
- Added traits
- Added utility commands

## TODO

- Automated tests