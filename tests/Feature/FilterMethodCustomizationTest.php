<?php

namespace Ambengers\QueryFilter\Tests\Feature;

use Ambengers\QueryFilter\AbstractQueryLoader;
use Ambengers\QueryFilter\RequestQueryBuilder;
use Ambengers\QueryFilter\Tests\FeatureTest;
use Ambengers\QueryFilter\Tests\Filters\PostMethodBasedFilters;
use Ambengers\QueryFilter\Tests\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\TestResponse;

class FilterMethodCustomizationTest extends FeatureTest
{
    /** @test */
    public function can_customize_filter_method_name()
    {
        Config::set('query_filter.method', 'fooBar');

        // Since our service provider has already been loaded before we hit this test
        // we will just have to manually register our eloquent builder macro here...
        $this->bootEloquentFilterMacro();

        $post1 = factory(Post::class)->create(['subject' => 'foobar barbazz']);
        $post2 = factory(Post::class)->create(['subject' => 'bang bang']);

        $posts = Post::fooBar(
            new PostMethodBasedFilters(
                app(Request::class)->merge(['search' => 'barbazz'])
            )
        );

        TestResponse::fromBaseResponse(
            app(Response::class)->setContent($posts)
        )->assertJsonFragment(['subject' => $post1->subject])
        ->assertJsonMissing(['subject' => $post2->subject]);
    }

    /**
     * Simulate the booting of the filter macro
     *
     * @return void
     */
    protected function bootEloquentFilterMacro()
    {
        $method = config('query_filter.method', 'filter');

        Builder::macro($method, function (RequestQueryBuilder $filters) {
            if ($filters instanceof AbstractQueryLoader) {
                return $filters->getFilteredModel($this);
            }
            return $filters->getPaginated($this);
            return $filters->getFilteredModelCollection($this);
        });
    }
}
