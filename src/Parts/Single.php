<?php

namespace Coretik\Navigation\Parts;

use Coretik\Core\Collection;
use Coretik\Core\Interfaces\CollectionInterface;

class Single extends Part
{
    protected $model;

    public function title(): string
    {
        return $this->title ?? ($this->model()?->title() ?? \get_the_title());
    }

    public function url(): string
    {
        return $this->model()?->permalink() ?? (string)\get_permalink();
    }

    /**
     * The coretik model, null when the post type is not declared in the coretik schema
     */
    public function model()
    {
        if (!isset($this->model)) {
            $this->model = static::$navigation->builder(\get_post_type())?->model(\get_the_ID());
        }
        return $this->model;
    }

    public function setModel($model): self
    {
        $this->model = $model;
        return $this;
    }

    protected function postType(): string
    {
        return (string)($this->model()?->name() ?? \get_post_type());
    }

    protected function hasArchive(): bool
    {
        $builder = static::$navigation->builder($this->postType());
        if (!empty($builder)) {
            return (bool)$builder->args()->get('has_archive');
        }
        return (bool)(\get_post_type_object($this->postType())->has_archive ?? false);
    }

    public function breadcrumb(): CollectionInterface
    {
        $parts = new Collection();

        if ($this->hasArchive()) {
            $part = static::$navigation->newPart('archive')->setPostType($this->postType());
            $parts->set(\get_class($part), $part);
        }

        $model = $this->model();
        if (!empty($model) && \method_exists($model, 'category')) {
            $category = $model->category();
            if ($category instanceof \WP_Term) {
                $category = static::$navigation->builder($category->taxonomy)?->model($category->term_id, $category);
            }
            if (!empty($category)) {
                $part = static::$navigation->newPart('taxonomy')->setModel($category);
                $parts = $parts->replace($part->breadcrumb());
            }
        }

        $parts->set(\get_class($this), $this);

        return $parts;
    }
}
