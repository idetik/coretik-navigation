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
        return $this->title ?? ($this->model()?->title() ?? (string)\single_term_title('', false));
    }

    public function url(): string
    {
        if (!empty($this->model())) {
            return $this->model()->permalink();
        }
        $link = \get_term_link(\get_queried_object());
        return \is_string($link) ? $link : '';
    }

    /**
     * The coretik model, null when the taxonomy is not declared in the coretik schema
     */
    public function model()
    {
        if (!isset($this->model)) {
            $term = \get_queried_object();
            if ($term instanceof \WP_Term) {
                $this->model = static::$navigation->builder($term->taxonomy)?->model($term->term_id, $term);
            }
        }
        return $this->model;
    }

    public function setModel($model): self
    {
        $this->model = $model;
        $this->parents = null;
        return $this;
    }

    public function parents(): array
    {
        if (!isset($this->parents)) {
            $this->parents = [];
            $model = $this->model();
            if (empty($model)) {
                return $this->parents;
            }
            $builder = static::$navigation->builder($model->taxonomy);
            $parents = \array_reverse(\get_ancestors($model->id(), $model->taxonomy));
            foreach ($parents as $parent_id) {
                $parent = $builder?->model($parent_id, \get_term((int)$parent_id, $model->taxonomy));
                if (!empty($parent)) {
                    $this->parents[] = (new static())->setModel($parent);
                }
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
