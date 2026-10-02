<?php
namespace Tests\Feature;
use Database\Seeders\ShiftSeeder; use Illuminate\Foundation\Testing\RefreshDatabase; use Tests\TestCase;
class ShiftSeederTest extends TestCase { use RefreshDatabase; public function test_three_factory_shifts_are_seeded(): void { $this->seed(ShiftSeeder::class); $this->assertDatabaseCount('shifts',3); $this->assertDatabaseHas('shifts',['code'=>'SHIFT-3','crosses_midnight'=>true]); } }
