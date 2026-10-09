<?php

namespace Coretik\Navigation\Tests\Unit;

use Brain\Monkey\Functions;
use Coretik\Navigation\Parts\Archive;
use Coretik\Navigation\Parts\Part;
use Coretik\Navigation\Parts\Search;
use Coretik\Navigation\Parts\Single;
use Coretik\Navigation\Parts\Taxonomy;
use Coretik\Navigation\Tests\TestCase;

class NavigationTest extends TestCase
{
    private function onArchivePage(?string $archivePostType): void
    {
        Functions\when('get_the_ID')->justReturn(10);
        Functions\when('is_page')->justReturn(true);
        Functions\when('get_page_template_slug')->justReturn('template-archive.php');
        $this->builder('page', [], $this->model(['archive_post_type' => $archivePostType]));
    }

    public function testIsPostTypeArchiveOnArchivePage(): void
    {
        $this->onArchivePage('event');
        $this->builder('event', ['has_archive' => true, 'use_archive_page' => true]);

        $this->assertTrue($this->navigation->isPostTypeArchive('event'));
        $this->assertFalse($this->navigation->isPostTypeArchive('news'));
    }

    public function testArchivePageWithoutPostType(): void
    {
        $this->onArchivePage(null);

        $this->assertFalse($this->navigation->isPageArchive());
    }

    public function testArchivePageWithPostTypeUnknownToCoretik(): void
    {
        $this->onArchivePage('product');

        $this->assertFalse($this->navigation->isPageArchive());
    }

    public function testSingleOfPostTypeUnknownToCoretik(): void
    {
        // e.g. a WooCommerce product
        Functions\when('is_single')->justReturn(true);
        Functions\when('get_post_type')->justReturn('product');
        Functions\when('get_the_ID')->justReturn(42);
        Functions\when('get_the_title')->justReturn('Tent');
        Functions\when('get_permalink')->justReturn('https://example.com/product/tent/');
        Functions\when('get_post_type_object')->justReturn((object)['has_archive' => false]);

        $current = $this->navigation->current();

        $this->assertInstanceOf(Single::class, $current);
        $this->assertSame('Tent', $current->title());
        $this->assertSame('https://example.com/product/tent/', $current->url());
        $this->assertCount(1, $current->breadcrumb());
    }

    public function testAuthorArchiveIsNotAPostTypeArchive(): void
    {
        Functions\when('is_archive')->justReturn(true);
        Functions\when('is_author')->justReturn(true);
        Functions\when('get_the_archive_title')->justReturn('Author: <span>John</span>');

        $current = $this->navigation->current();

        $this->assertNotInstanceOf(Archive::class, $current);
        $this->assertSame('Author: John', $current->title());
    }

    public function testTagArchiveIsATaxonomy(): void
    {
        Functions\when('is_archive')->justReturn(true);
        Functions\when('is_tag')->justReturn(true);

        $this->assertInstanceOf(Taxonomy::class, $this->navigation->current());
    }

    public function testArchiveWithoutRewriteSlug(): void
    {
        $this->builder('event', ['has_archive' => true, 'use_archive_page' => true, 'rewrite' => false]);
        Functions\expect('get_page_by_path')->once()->with('event')->andReturn(null);

        $archive = (new Archive())->setPostType('event');

        $this->assertFalse($archive->isPageArchive());
    }

    public function testPartWithoutTitleOrUrl(): void
    {
        $part = new Part();

        $this->assertSame('', $part->title());
        $this->assertSame('', $part->url());
    }

    public function testSearchTitleHasNoHtml(): void
    {
        Functions\when('get_search_query')->justReturn('tent');

        $this->assertSame('Résultat de la recherche pour "tent"', (new Search())->title());
    }

    public function testTaxonomyParentsFollowTheModel(): void
    {
        Functions\when('get_ancestors')->alias(fn ($id) => [1 => [], 2 => [1]][$id]);
        Functions\when('get_term')->justReturn(null);
        $this->builder('category', [], $this->model(['taxonomy' => 'category'], ['id' => 1]));

        $part = new Taxonomy();
        $part->setModel($this->model(['taxonomy' => 'category'], ['id' => 1]));
        $this->assertCount(0, $part->parents());

        // The same part reused for another term (parts are shared by the factory)
        $part->setModel($this->model(['taxonomy' => 'category'], ['id' => 2]));
        $this->assertCount(1, $part->parents());
    }

    public function testBreadcrumbPartsAreNotShared(): void
    {
        Functions\when('get_ancestors')->justReturn([]);
        $category = $this->model(['taxonomy' => 'category'], ['id' => 5, 'title' => 'Camping']);
        $event = new class ($category) {
            public function __construct(private $category)
            {
            }

            public function name(): string
            {
                return 'event';
            }

            public function title(): string
            {
                return 'Concert';
            }

            public function category()
            {
                return $this->category;
            }
        };
        $this->builder('event', ['has_archive' => false], $event);

        Functions\when('get_post_type')->justReturn('event');
        Functions\when('get_the_ID')->justReturn(42);

        $single = new Single(true);
        $first = $single->breadcrumb()->map(fn ($part) => $part->title())->values()->all();

        // Another part of the page uses the shared taxonomy part
        $this->navigation->partsFactory('taxonomy')->setModel($this->model(['taxonomy' => 'category'], ['id' => 9, 'title' => 'Other']));

        $this->assertSame(['Camping', 'Concert'], $first);
        $this->assertSame($first, $single->breadcrumb()->map(fn ($part) => $part->title())->values()->all());
    }
}
