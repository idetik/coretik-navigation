<?php

namespace Coretik\Navigation\Parts;

use Coretik\Core\Collection;
use Coretik\Core\Interfaces\CollectionInterface;

class Single extends Part
{
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
            $this->model = app()->schema(\get_post_type())->model(\get_the_ID());
        }
        return $this->model;
    }

    public function setModel($model)
    {
        $this->model = $model;
    }

    public function breadcrumb(): CollectionInterface
    {
        $parts = new Collection();

        $builder = app()->schema($this->model()->name());
        if ($builder->args()->get('has_archive')) {
            $part = static::$navigation->partsFactory('archive')->setPostType($builder->getName());
            $parts->set(\get_class($part), $part);
        }

        if (\method_exists($this->model(), 'category')) {
            $category = $this->model()->category();
            if (!empty($category)) {
                if ($category instanceof \WP_Term) {
                    $category = app()->schema($category->taxonomy)->model($category->term_id, $category);
                }
                $part = static::$navigation->partsFactory('taxonomy')->setModel($category);
                $parts->replace($part->breadcrumb());
            }
        }

        $parts->set(\get_class($this), $this);

        return $parts;
    }
}
