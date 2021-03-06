<?php

namespace Integration\Controllers;

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryPageController;
use App\Http\Controllers\TagController;
use App\Http\Middleware\VerifyCsrfToken;
use App\Models\Category;
use App\Models\Tag;
use Faker\Factory;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CategoryStoreControllerTest extends TestCase
{
    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(VerifyCsrfToken::class);
        $this->faker = Factory::create();
        DB::beginTransaction();
    }

    /**
     * Assert, that session will have errors if there will be incorrect data
     *
     * @dataProvider incorrectDataProvider
     * @param        array $data
     * @param        array $expectedErrors
     * @return       void
     */
    public function test_incorrect_data(array $data, array $expectedErrors)
    {
        $this->post(route('category.store'), $data)->assertSessionHasErrors($expectedErrors);
    }

    public function incorrectDataProvider()
    {
        $faker = Factory::create();

        return [
            'empty_name' =>
                [
                    [
                        'name' => ''
                    ],
                    [
                        'name'
                    ]
                ],

            'name_over_length' => [
                [
                    'name' => $faker->lexify(str_repeat('?', CategoryController::MAX_NAME_LENGTH + 1))
                ],
                [
                    'name'
                ]
            ]

        ];
    }

    /**
     * Assert, that there will be redirect to page with tags and that tag will be saved in database, if
     * controller will receive correct data
     *
     * @return void
     */
    public function test_correct_save()
    {
        $name = $this->faker->name;
        $this->post(route('category.store'), ['name' => $name])->assertRedirect(route('category.index'));
        $this->assertDatabaseHas('categories', ['name' => $name]);
    }

    /**
     * Assert, that user cannot save tag with name, that already exist in database
     *
     * @return void
     */
    public function test_duplicate()
    {
        $tag = Category::factory()->create();
        $this->post(route('category.store'), ['name' => $tag->getAttribute('name')])
            ->assertSessionHasErrors('name');
    }

    public function tearDown(): void
    {
        DB::rollBack();
        parent::tearDown();
    }
}
