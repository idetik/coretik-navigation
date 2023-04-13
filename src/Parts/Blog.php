<?php

namespace Coretik\Navigation\Parts;

use Coretik\Core\Collection;
use Coretik\Core\Interfaces\CollectionInterface;

class Blog extends Part
{
    public function title(): string
    {
        return $this->title ?? \get_the_title(\get_option('page_for_posts'));
    }

    public function url(): string
    {
        return \get_post_type_archive_link('post');
    }

    public function breadcrumb(): CollectionInterface
    {
        $parts = new Collection();
        $parts->set(\get_class($this), $this);

        return $parts;
    }
}
