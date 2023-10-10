<?php

namespace Coretik\Navigation\Parts;

use Coretik\Core\Collection;
use Coretik\Core\Interfaces\CollectionInterface;

class Part implements PartInterface
{
    protected $current;
    protected $title;
    protected $url;
    protected static $navigation;
    protected bool $noIndex = false;
    protected bool $noFollow = false;

    public function __construct(bool $current = false)
    {
        if (!isset(static::$navigation)) {
            static::$navigation = app()->navigation();
        }
        $this->setCurrent($current);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function url(): string
    {
        return $this->url;
    }

    public function current(): bool
    {
        return $this->current;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function setCurrent(bool $current = true): self
    {
        $this->current = $current;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'title' => $this->title(),
            'url' => $this->url(),
            'current' => $this->current(),
        ];
    }

    public function breadcrumb(): CollectionInterface
    {
        $parts = new Collection();
        $parts->set(\get_class($this), $this);
        return $parts;
    }

    /**
     * @param bool $noIndex 
     * @return self
     */
    public function setNoIndex(bool $noIndex): self
    {
        $this->noIndex = $noIndex;
        return $this;
    }

    /**
     * @param bool $noFollow 
     * @return self
     */
    public function setNoFollow(bool $noFollow): self
    {
        $this->noFollow = $noFollow;
        return $this;
    }

    /**
     * @return bool
     */
    public function getNoIndex(): bool
    {
        return $this->noIndex;
    }

    /**
     * @return bool
     */
    public function getNoFollow(): bool
    {
        return $this->noFollow;
    }
}
