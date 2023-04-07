<?php

namespace Coretik\Navigation\Parts;

use Coretik\Core\Collection;
use Coretik\Core\Interfaces\CollectionInterface;

class Archive extends Part
{
    protected $postType;
    protected $page;

    public function postType()
    {
        if (empty($this->postType)) {
            $this->postType = static::$navigation->currentPostType();
        }
        return $this->postType;
    }

    public function setPostType(string $post_type)
    {
        $this->postType = $post_type;
        return $this;
    }

    public function isPageArchive(): bool
    {
        if (isset($this->page)) {
            return !!$this->page;
        }

        $builder = app()->schema($this->postType());
        if ($builder->args()->get('use_archive_page')) {
            global $wp_rewrite;
            $path = true === $builder->args()->get('has_archive') ? $builder->args()->get('rewrite')['slug'] : $builder->args()->get('has_archive');
            if ($builder->args()->get('rewrite')['with_front']) {
                $path = substr($wp_rewrite->front, 1) . $path;
            } else {
                $path = $wp_rewrite->root . $path;
            }
            if ($page = \get_page_by_path($path)) {
                $this->page = $page;
                return true;
            }
        }
        $this->page = false;
        return false;
    }

    protected function model()
    {
        if ($this->isPageArchive()) {
            return app()->schema('page')->model($this->page->ID, $this->page);
        }

        return null;
    }

    public function title(): string
    {
        return $this->isPageArchive() ? $this->model()->title() : ucfirst(app()->schema($this->postType())->args()->get('labels')['plural']);
    }

    public function url(): string
    {
        return \get_post_type_archive_link($this->postType());
    }

    public function breadcrumb(): CollectionInterface
    {
        $hasFilter = false;
        $parts = new Collection();

        $parts->set(\get_class($this), $this);

        if ($this->isPageArchive()) {
            if (!empty($this->model()->currentFilters())) {
                $hasFilter = true;
                $tax = $this->model()->currentFilters();
                $collection = app()->schema(key($tax))->query()->set('slug', current($tax))->set('hide_empty', false)->collection();
                if ($collection->count() > 0) {
                    $termModel = $collection->first();
                    $part = static::$navigation->partsFactory('taxonomy')->setModel($termModel);
                    $parts->replace($part->breadcrumb());
                }
            }

            if (is_tax()) {
                $hasFilter = true;
                $termModel = app()->schema(\get_queried_object()->taxonomy)->model(\get_queried_object()->term_id, \get_queried_object());
                $part = static::$navigation->partsFactory('taxonomy')->setModel($termModel);
                $parts->replace($part->breadcrumb());
            }
        }

        if ($this->current() && $hasFilter) {
            $this->setCurrent(false);
            $parts->last()->setCurrent();
        }

        return $parts;
    }
}
