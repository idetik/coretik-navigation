<?php

namespace Coretik\Navigation;

use Coretik\Core\Interfaces\CollectionInterface;
use Coretik\Navigation\Parts\PartInterface;

use function Globalis\WP\Cubi\get_current_url;

class Navigation
{
    protected $container;
    protected $parts = [];

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function title(): string
    {
        return \apply_filters('coretik/navigation/title', $this->current()->title(), $this);
    }

    public function breadcrumb(): CollectionInterface
    {
        return \apply_filters('coretik/navigation/breadcrumb', $this->current()->breadcrumb(), $this);
    }

    /**
     * The coretik builder of a post type / taxonomy, null when it is not declared in the coretik schema
     */
    public function builder(?string $name)
    {
        return empty($name) ? null : $this->container->get('schema')->get($name);
    }

    public function currentPostType(): string
    {
        if ($this->isPageArchive()) {
            return $this->archivePostType(\get_the_ID());
        }

        return (string)\get_post_type();
    }

    /**
     * Post types archives (not author, date or taxonomy archives)
     */
    public function isArchive(): bool
    {
        return \apply_filters('coretik/navigation/isArchive', \is_post_type_archive() || $this->isPageArchive(), $this);
    }

    /**
     * Post type of an archive page (archive_post_type meta), empty if none
     */
    protected function archivePostType(int $id): string
    {
        $model = $this->builder('page')?->model($id);
        return (string)($model->archive_post_type ?? '');
    }

    public function isPageArchive(int $id = 0): bool
    {
        if (!$id) {
            $id = get_the_ID();
        }

        if (!is_page($id)) {
            return false;
        }

        if (\get_page_template_slug($id) !== 'template-archive.php') {
            return false;
        }

        $builder = $this->builder($this->archivePostType($id));
        if (empty($builder)) {
            return false;
        }

        if (!$builder->args()->get('has_archive') || !$builder->args()->get('use_archive_page')) {
            return false;
        }

        return true;
    }

    public function isPostTypeArchive(string $postType): bool
    {
        if (!$this->isArchive()) {
            return false;
        }
        return $postType === $this->currentPostType();
    }

    public function current(): PartInterface
    {
        $current = \apply_filters('coretik/navigation/current', null, $this);

        if (!empty($current)) {
            return $current;
        }

        switch (true) {
            case \is_home():
                return $this->partsFactory('blog')->setCurrent();
            case \is_404():
                return $this->partsFactory('page404')->setCurrent();
            case \is_search():
                return $this->partsFactory('search')->setCurrent();
            case \is_tax():
            case \is_category():
            case \is_tag():
                return $this->partsFactory('taxonomy')->setCurrent();
            case \is_author():
            case \is_date():
                return $this->genericPart(\wp_strip_all_tags(\get_the_archive_title()), get_current_url());
            case $this->isArchive():
                return $this->partsFactory('archive')->setCurrent();
            case \is_page():
                return $this->partsFactory('page')->setCurrent();
            case \is_single():
                return $this->partsFactory('single')->setCurrent();
            default:
                return $this->genericPart(\get_the_title(), get_current_url());
        }
    }

    protected function genericPart(string $title, string $url): PartInterface
    {
        $part = $this->partsFactory('part')->setCurrent();
        if ($part instanceof Parts\Part) {
            $part->setTitle($title)->setUrl($url);
        }
        return $part;
    }

    /**
     * A new part, for breadcrumbs: parts given by partsFactory() are shared
     */
    public function newPart(string $partName): PartInterface
    {
        return clone $this->partsFactory($partName);
    }

    public function partsFactory($partName, array $args = []): PartInterface
    {
        if (\array_key_exists($partName, $this->parts)) {
            return $this->parts[$partName];
        }

        $part = \apply_filters('coretik/navigation/part/name=' . $partName, null, $args, $this);

        if (!empty($part) && $part instanceof PartInterface) {
            $this->parts[$partName] = $part;
        } else {
            switch ($partName) {
                case 'part':
                    return new Parts\Part(...$args);
                default:
                    $classname = __NAMESPACE__ . "\\Parts\\" . \ucfirst($partName);
                    $this->parts[$partName] = new $classname(...$args);
                    break;
            }
        }

        return $this->parts[$partName];
    }
}
