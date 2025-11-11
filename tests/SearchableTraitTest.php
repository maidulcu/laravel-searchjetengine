<?php

namespace SearchJet\Laravel\Tests;

use Illuminate\Database\Eloquent\Model;
use SearchJet\Laravel\Traits\Searchable;

class SearchableTraitTest extends TestCase
{
    public function test_model_can_use_searchable_trait()
    {
        $model = new TestSearchableModel();

        $this->assertInstanceOf(Model::class, $model);
        $this->assertTrue(method_exists($model, 'searchJetIndex'));
        $this->assertTrue(method_exists($model, 'toSearchJetDocument'));
        $this->assertTrue(method_exists($model, 'searchJetAddToIndex'));
    }

    public function test_searchable_uses_table_name_as_default_index()
    {
        $model = new TestSearchableModel();
        $this->assertEquals('test_searchable_models', $model->searchJetIndex());
    }

    public function test_model_should_be_indexed_by_default()
    {
        $model = new TestSearchableModel();
        $this->assertTrue($model->shouldBeSearchJetIndexed());
    }

    public function test_static_search_methods_exist()
    {
        $this->assertTrue(method_exists(TestSearchableModel::class, 'searchJet'));
        $this->assertTrue(method_exists(TestSearchableModel::class, 'searchJetGet'));
        $this->assertTrue(method_exists(TestSearchableModel::class, 'searchJetFirst'));
        $this->assertTrue(method_exists(TestSearchableModel::class, 'searchJetCount'));
        $this->assertTrue(method_exists(TestSearchableModel::class, 'searchJetPaginate'));
    }
}

class TestSearchableModel extends Model
{
    use Searchable;

    protected $fillable = ['title', 'description'];
}
