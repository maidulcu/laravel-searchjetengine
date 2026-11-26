<?php

namespace SearchJet\Laravel\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use SearchJet\Laravel\Traits\Searchable;

class Product extends Model
{
    use Searchable;

    protected $table = 'products';

    protected $guarded = [];

    public $timestamps = false;

    public function toSearchJetDocument(): array
    {
        return ['title' => $this->name, 'price' => $this->price];
    }
}

class SearchableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price')->nullable();
        });
    }

    public function test_sync_sends_typed_document(): void
    {
        Http::fake([self::BASE.'/v1/index/bulk' => Http::response(['taskUid' => 1])]);
        $product = Product::create(['name' => 'Laptop', 'price' => 999]);

        $product->searchJetSync();

        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r->url() === self::BASE.'/v1/index/bulk'
            && $r->data() === ['documents' => [[
                'title' => 'Laptop',
                'price' => 999,
                'id' => 'products-'.$product->id,
                'type' => 'products',
            ]]]);
    }

    public function test_remove_deletes_by_document_id(): void
    {
        Http::fake([self::BASE.'/v1/index/delete' => Http::response(['success' => true])]);
        $product = Product::create(['name' => 'Laptop']);

        $product->searchJetRemove();

        Http::assertSent(fn (Request $r) => $r->method() === 'DELETE'
            && $r->data() === ['id' => 'products-'.$product->id]);
    }

    public function test_search_adds_type_filter(): void
    {
        Http::fake([self::BASE.'/v1/search*' => Http::response(['search_id' => 'x', 'results' => []])]);

        Product::searchJet('laptop', ['filters' => 'price < 1000']);

        Http::assertSent(function (Request $r) {
            parse_str(parse_url($r->url(), PHP_URL_QUERY), $q);

            return $q['filters'] === '(price < 1000) AND type = "products"';
        });
    }

    public function test_search_models_loads_models_in_result_order_including_free_plan_ids(): void
    {
        $a = Product::create(['name' => 'A']);
        $b = Product::create(['name' => 'B']);

        Http::fake([self::BASE.'/v1/search*' => Http::response(['search_id' => 'x', 'results' => [
            ['id' => 'site_9_products-'.$b->id],
            ['id' => 'products-'.$a->id],
            ['id' => 'products-999'],
            ['id' => 'posts-1'],
        ]])]);

        $models = Product::searchJetModels('a');

        $this->assertSame(['B', 'A'], $models->pluck('name')->all());
    }

    public function test_import_command_sends_all_models_in_chunks(): void
    {
        Http::fake([self::BASE.'/v1/index/bulk' => Http::response(['taskUid' => 1])]);
        foreach (range(1, 5) as $i) {
            Product::create(['name' => "P{$i}"]);
        }

        $this->artisan('searchjet:import', ['model' => Product::class, '--chunk' => 2])
            ->assertSuccessful();

        Http::assertSentCount(3);
    }

    public function test_import_command_rejects_non_searchable_class(): void
    {
        $this->artisan('searchjet:import', ['model' => \stdClass::class])->assertFailed();
    }

    public function test_search_command(): void
    {
        Http::fake([self::BASE.'/v1/search*' => Http::response([
            'search_id' => 'abc', 'total' => 1, 'processing_time_ms' => 3,
            'results' => [['id' => 'products-1', 'type' => 'products', 'title' => 'Laptop']],
        ])]);

        $this->artisan('searchjet:search', ['query' => 'laptop'])
            ->expectsOutputToContain('search_id abc')
            ->assertSuccessful();
    }
}
