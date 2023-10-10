<?php

namespace Coretik\Navigation\Parts;

use Coretik\Core\Collection;
use Coretik\Core\Interfaces\CollectionInterface;

class Page extends Part
{
    protected $id;
    protected $parents;
    protected bool $noIndex = false;
    protected bool $noFollow = false;

    protected function id()
    {
        if (empty($this->id)) {
            $this->setId(\get_the_ID());
        }
        return $this->id;
    }

    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }

    public function title(): string
    {
        return $this->title ?? app()->schema('page')->model($this->id())->title();
    }

    public function url(): string
    {
        return app()->schema('page')->model($this->id())->permalink();
    }

    public function parents()
    {
        if (!isset($this->parents)) {
            $this->parents = [];
            $parents = \array_reverse(\get_ancestors($this->id(), 'page'));
            foreach ($parents as $parent_id) {
                $this->parents[] = (new static())->setId($parent_id);
            }
        }
        return $this->parents;
    }

    public function breadcrumb(): CollectionInterface
    {
        $parts = new Collection($this->parents());
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
