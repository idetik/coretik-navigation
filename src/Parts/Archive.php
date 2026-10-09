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
        $this->page = null;
        return $this;
    }

    public function isPageArchive(): bool
    {
        if (isset($this->page)) {
            return !!$this->page;
        }

        $builder = static::$navigation->builder($this->postType());
        if (!empty($builder) && $builder->args()->get('use_archive_page')) {
            global $wp_rewrite;
            $rewrite = $builder->args()->get('rewrite');
            $hasArchive = $builder->args()->get('has_archive');
            $path = \is_string($hasArchive) ? $hasArchive : (\is_array($rewrite) && !empty($rewrite['slug']) ? $rewrite['slug'] : $this->postType());
            if (\is_array($rewrite) && ($rewrite['with_front'] ?? true) && isset($wp_rewrite->front)) {
                $path = substr($wp_rewrite->front, 1) . $path;
            } else {
                $path = ($wp_rewrite->root ?? '') . $path;
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
            return \Coretik\App::instance()->schema('page')->model($this->page->ID, $this->page);
        }

        return null;
    }

    public function title(): string
    {
        if (isset($this->title)) {
            return $this->title;
        }
        if ($this->isPageArchive()) {
            return $this->model()->title();
        }
        $builder = static::$navigation->builder($this->postType());
        $label = !empty($builder) ? ($builder->args()->get('labels')['plural'] ?? '') : (\get_post_type_object($this->postType())->labels->name ?? '');
        return ucfirst((string)$label);
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
                $collection = \Coretik\App::instance()->schema(key($tax))->query()->set('slug', current($tax))->set('hide_empty', false)->collection();
                if ($collection->count() > 0) {
                    $termModel = $collection->first();
                    $part = static::$navigation->newPart('taxonomy')->setModel($termModel);
                    $parts = $parts->replace($part->breadcrumb());
                }
            }

            if (is_tax()) {
                $hasFilter = true;
                $termModel = \Coretik\App::instance()->schema(\get_queried_object()->taxonomy)->model(\get_queried_object()->term_id, \get_queried_object());
                $part = static::$navigation->newPart('taxonomy')->setModel($termModel);
                $parts = $parts->replace($part->breadcrumb());
            }
        }

        if ($this->current() && $hasFilter) {
            $this->setCurrent(false);
            $parts->last()->setCurrent();
        }

        return $parts;
    }
}
