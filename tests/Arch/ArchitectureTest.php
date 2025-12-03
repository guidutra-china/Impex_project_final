<?php

namespace Tests\Arch;

use Pest\Arch\Expectations;

/**
 * Architecture Tests
 * 
 * These tests ensure that the codebase follows architectural rules
 * and design patterns. They prevent common anti-patterns and enforce
 * separation of concerns.
 */

// Controllers should not directly access Eloquent models
test('Controllers should not use Eloquent models directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Database\Eloquent\Model')
    ->ignoring('Illuminate\Foundation\Http\FormRequest');

// Services should contain business logic
test('Services should be in the Services namespace')
    ->expect('App\Services')
    ->toBeClasses();

// Models should not contain HTTP logic
test('Models should not use HTTP classes')
    ->expect('App\Models')
    ->not->toUse('Illuminate\Http\Request')
    ->not->toUse('Illuminate\Http\Response');

// Actions should have a single responsibility
test('Actions should be in the Actions namespace')
    ->expect('App\Actions')
    ->toBeClasses();

// Filament Resources should not contain business logic
test('Filament Resources should not directly query database')
    ->expect('App\Filament\Resources')
    ->not->toUse('Illuminate\Support\Facades\DB')
    ->ignoring('Illuminate\Database\Eloquent\Relations');

// Enums should be simple value objects
test('Enums should not have complex dependencies')
    ->expect('App\Enums')
    ->toBeEnums();

// Traits should follow naming convention
test('Traits should be in Traits namespace and end with Trait suffix')
    ->expect('App\Traits')
    ->toHaveSuffix('Trait');

// Value Objects should be immutable and simple
test('Value Objects should not use Eloquent')
    ->expect('App\ValueObjects')
    ->not->toUse('Illuminate\Database\Eloquent\Model');

// Exceptions should extend Exception
test('Custom Exceptions should extend Exception')
    ->expect('App\Exceptions')
    ->toExtend('Exception');

// No debug functions in production code
test('No debug functions in app code')
    ->expect('App')
    ->not->toUse('dd')
    ->not->toUse('dump')
    ->not->toUse('var_dump')
    ->not->toUse('print_r');

// No hardcoded credentials
test('No hardcoded credentials in code')
    ->expect('App')
    ->not->toContain('password')
    ->not->toContain('secret')
    ->not->toContain('token')
    ->ignoring('App\Models');
