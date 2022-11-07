<?php

namespace Coretik\Navigation\Parts;

use Coretik\Core\Interfaces\CollectionInterface;

interface PartInterface
{

    public function title(): string;
    public function url(): string;
    public function current(): bool;
    public function setCurrent(bool $current = true): self;
    public function toArray(): array;
    public function breadcrumb(): CollectionInterface;
}
