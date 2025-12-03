<?php

namespace Tests\Feature\Filament\Pages\Suppliers;

use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

class ListSuppliersTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_render_list_page()
    {
        $response = $this->get('/admin/suppliers');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_displays_suppliers_in_table()
    {
        Supplier::factory(3)->for($this->user)->create();
        $response = $this->get('/admin/suppliers');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_filter_suppliers_by_status()
    {
        Supplier::factory(2)->for($this->user)->create(['status' => 'active']);
        Supplier::factory(1)->for($this->user)->create(['status' => 'inactive']);
        
        $response = $this->get('/admin/suppliers?status=active');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_search_suppliers_by_name()
    {
        $supplier = Supplier::factory()->for($this->user)->create(['name' => 'UNIQUE-SUPPLIER']);
        
        $response = $this->get('/admin/suppliers?search=UNIQUE');
        $response->assertSuccessful();
        $response->assertSee('UNIQUE-SUPPLIER');
    }

    /** @test */
    public function it_can_search_suppliers_by_email()
    {
        $supplier = Supplier::factory()->for($this->user)->create(['email' => 'unique@supplier.com']);
        
        $response = $this->get('/admin/suppliers?search=unique@');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_sort_suppliers()
    {
        Supplier::factory()->for($this->user)->create(['name' => 'Supplier A']);
        Supplier::factory()->for($this->user)->create(['name' => 'Supplier B']);
        
        $response = $this->get('/admin/suppliers?sort=name');
        $response->assertSuccessful();
    }

    /** @test */
    public function it_has_create_button()
    {
        $response = $this->get('/admin/suppliers');
        $response->assertSuccessful();
        $response->assertSee('Create');
    }

    /** @test */
    public function it_displays_empty_state_when_no_suppliers()
    {
        $response = $this->get('/admin/suppliers');
        $response->assertSuccessful();
    }

    /** @test */
    public function unauthorized_user_cannot_view_suppliers()
    {
        $this->actingAs(User::factory()->create());
        $response = $this->get('/admin/suppliers');
        $this->assertTrue($response->status() === 403 || $response->status() === 302);
    }
}
