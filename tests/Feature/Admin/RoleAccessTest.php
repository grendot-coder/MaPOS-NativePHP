<?php

use App\Enums\UserRole;
use App\Models\User;

test('unauthenticated users are redirected to login', function () {
    $response = $this->get('/');
    $response->assertRedirect(route('login'));

    $response = $this->get('/dashboard');
    $response->assertRedirect(route('login'));

    $response = $this->get('/admin/dashboard');
    $response->assertRedirect(route('login'));

    $response = $this->get('/kasir/pos');
    $response->assertRedirect(route('login'));
});

test('dashboard dispatches admin to admin dashboard', function () {
    $admin = User::where('role', UserRole::Admin)->first()
        ?? User::factory()->create(['role' => UserRole::Admin]);

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertRedirect(route('admin.dashboard'));
});

test('dashboard dispatches kasir to kasir pos', function () {
    $kasir = User::where('role', UserRole::Kasir)->first()
        ?? User::factory()->create(['role' => UserRole::Kasir]);

    $response = $this->actingAs($kasir)->get('/dashboard');
    $response->assertRedirect(route('kasir.pos'));
});

test('admin can access all admin management pages', function () {
    $admin = User::where('role', UserRole::Admin)->first()
        ?? User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('admin.golongan.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.produk.index'))->assertOk();
    $this->actingAs($admin)->get(route('admin.produk.create'))->assertOk();
});

test('kasir is forbidden from accessing admin pages', function () {
    $kasir = User::where('role', UserRole::Kasir)->first()
        ?? User::factory()->create(['role' => UserRole::Kasir]);

    $this->actingAs($kasir)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($kasir)->get(route('admin.golongan.index'))->assertForbidden();
    $this->actingAs($kasir)->get(route('admin.produk.index'))->assertForbidden();
});

test('kasir and admin can both access kasir pos page', function () {
    $kasir = User::where('role', UserRole::Kasir)->first()
        ?? User::factory()->create(['role' => UserRole::Kasir]);

    $admin = User::where('role', UserRole::Admin)->first()
        ?? User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($kasir)->get(route('kasir.pos'))->assertOk();
    $this->actingAs($admin)->get(route('kasir.pos'))->assertOk();
});
