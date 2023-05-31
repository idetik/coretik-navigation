<?php

namespace Coretik\Navigation\Parts;

use Coretik\Core\Collection;
use Coretik\Core\Interfaces\CollectionInterface;

class Taxonomy extends Part
{
    protected $taxonomy;
    protected $parents;
    protected $model;

    public function title(): string
    {
        return $this->title ?? $this->model()->title();
    }

    public function url(): string
    {
        return $this->model()->permalink();
    }

    public function model()
    {
        if (!isset($this->model)) {
            $this->model = app()->schema(\get_queried_object()->taxonomy)->model(\get_queried_object()->term_id, \get_queried_object());
        }
        return $this->model;
    }

    public function setModel($model): self
    {
        $this->model = $model;
        return $this;
    }

    public function parents(): array
    {
        if (!isset($this->parents)) {
            $this->parents = [];
            $parents = \array_reverse(\get_ancestors($this->model()->id(), $this->model()->taxonomy));
            foreach ($parents as $parent_id) {
                $model = app()->schema($this->model()->taxonomy)->model($parent_id, \get_term((int)$parent_id, $this->model()->taxonomy));
                $this->parents[] = (new static())->setModel($model);
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
}
