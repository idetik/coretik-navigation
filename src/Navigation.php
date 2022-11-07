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

    public function currentPostType(): string
    {
        if ($this->isPageArchive()) {
            $model = $this->container->get('schema')->get('page')->model(\get_the_ID());
            return $model->archive_post_type;
        }

        return \get_post_type();
    }

    public function isArchive(): bool
    {
        return \apply_filters('coretik/navigation/isArchive', \is_archive() || $this->isPageArchive(), $this);
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

        try {
            $model = $this->container->get('schema')->get('page')->model($id);
            $builder = $this->container->get('schema')->get($model->archive_post_type);
        } catch (\Coretik\Core\Exception\ContainerValueNotFoundException $e) {
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
        $model = $this->container->schema('page')->model(\get_the_ID());
        return $postType === $model->archive_post_type;
    }

    public function current(): PartInterface
    {
        switch (true) {
            case \is_home():
                return $this->partsFactory('blog')->setCurrent();
            case \is_404():
                return $this->partsFactory('page404')->setCurrent();
            case \is_search():
                return $this->partsFactory('search')->setCurrent();
            case \is_tax():
            case \is_category():
                return $this->partsFactory('taxonomy')->setCurrent();
            case $this->isArchive():
                return $this->partsFactory('archive')->setCurrent();
            case \is_page():
                return $this->partsFactory('page')->setCurrent();
            case \is_single():
                return $this->partsFactory('single')->setCurrent();
            default:
                return $this->partsFactory('part')
                            ->setCurrent()
                            ->setTitle(\get_the_title())
                            ->setUrl(get_current_url());
        }
    }

    public function partsFactory($partName, array $args = []): PartInterface
    {
        if (\array_key_exists($partName, $this->parts)) {
            return $this->parts[$partName];
        }

        $part = \apply_filters('coretik/navigation/part/name=' . $partName, null, $this);

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
