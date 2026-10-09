<?php

namespace Coretik\Navigation\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Coretik\App;
use Coretik\Core\Container;
use Coretik\Core\Schema;
use Coretik\Navigation\Navigation;
use Coretik\Navigation\Parts\Part;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use MockeryPHPUnitIntegration;

    protected Navigation $navigation;
    protected $schema;

    /** @var array<string, mixed> Builders by name, null for a post type / taxonomy unknown to coretik */
    protected array $builders = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        Functions\when('__')->returnArg();
        Functions\when('wp_strip_all_tags')->alias(fn ($text) => \strip_tags($text));

        $this->schema = Mockery::mock(Schema::class);
        $this->schema->allows('get')->andReturnUsing(fn ($name) => $this->builders[$name] ?? null);
        $this->schema->allows('__invoke')->andReturnUsing(fn ($name = null) => null === $name ? $this->schema : ($this->builders[$name] ?? null));

        $container = new Container(['schema' => fn () => $this->schema]);
        $this->navigation = new Navigation($container);
        $container['navigation'] = fn () => $this->navigation;

        $app = (new \ReflectionClass(App::class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty(App::class, 'container'))->setValue($app, $container);
        (new \ReflectionProperty(App::class, 'instance'))->setValue(null, $app);
        (new \ReflectionProperty(Part::class, 'navigation'))->setValue(null, null);

        // No conditional tag is true unless a test says so
        foreach (['is_home', 'is_404', 'is_search', 'is_tax', 'is_category', 'is_tag', 'is_archive', 'is_post_type_archive', 'is_author', 'is_date', 'is_page', 'is_single'] as $tag) {
            Functions\when($tag)->justReturn(false);
        }
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(App::class, 'instance'))->setValue(null, null);
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * A coretik builder with the given args (has_archive, rewrite…)
     */
    protected function builder(string $name, array $args = [], $model = null)
    {
        $builder = Mockery::mock(\Coretik\Core\Builders\Interfaces\BuilderInterface::class);
        $builder->allows('getName')->andReturn($name);
        $builder->allows('args')->andReturn(new \Coretik\Core\Collection($args));
        $builder->allows('model')->andReturn($model);
        return $this->builders[$name] = $builder;
    }

    protected function model(array $props = [], array $methods = [])
    {
        $model = Mockery::mock();
        foreach ($props as $key => $value) {
            $model->$key = $value;
        }
        foreach ($methods as $method => $value) {
            $model->allows($method)->andReturn($value);
        }
        return $model;
    }
}
