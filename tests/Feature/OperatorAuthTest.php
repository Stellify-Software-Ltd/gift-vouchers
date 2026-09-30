<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

it('locks every desk page behind the operator session', function (string $path) {
    $voucher = issueVoucher();
    $path = str_replace('{id}', (string) $voucher->id, $path);

    $this->get($path)->assertRedirect('/login');
})->with([
    '/',
    '/vouchers',
    '/vouchers/new',
    '/redeem',
    '/liability',
    '/vouchers/{id}',
    '/vouchers/{id}/print',
]);

it('locks the write actions behind the operator session too', function () {
    $voucher = issueVoucher();

    $this->post('/vouchers', [])->assertRedirect('/login');
    $this->post("/vouchers/{$voucher->id}/redeem", [])->assertRedirect('/login');
    $this->post("/vouchers/{$voucher->id}/void", [])->assertRedirect('/login');
    $this->post("/vouchers/{$voucher->id}/email", [])->assertRedirect('/login');
});

it('signs the operator in with the key and out again', function () {
    // Wrong key is rejected and logged.
    Log::shouldReceive('warning')->once()->withArgs(fn ($msg) => str_contains($msg, 'Rejected operator key'));
    $this->post('/login', ['key' => 'wrong'])->assertSessionHasErrors('key');

    // Right key opens the desk.
    $this->post('/login', ['key' => 'test-operator-key'])->assertRedirect('/vouchers');
    $this->get('/vouchers')->assertOk()->assertSee('Voucher register');

    // Sign out ends it.
    $this->post('/logout')->assertRedirect('/login');
    $this->get('/vouchers')->assertRedirect('/login');
});

it('refuses to sign anyone in while no operator key is configured', function () {
    config(['vouchers.operator_key' => '']);

    $this->post('/login', ['key' => ''])->assertSessionHasErrors('key');
    $this->post('/login', ['key' => 'anything'])->assertSessionHasErrors('key');
    $this->get('/vouchers')->assertRedirect('/login');
});

it('rate limits sign-in attempts per ip', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/login', ['key' => 'wrong'])->assertSessionHasErrors('key');
    }

    $this->post('/login', ['key' => 'wrong'])->assertStatus(429);

    // Even the right key is refused while limited.
    $this->post('/login', ['key' => 'test-operator-key'])->assertStatus(429);

    RateLimiter::clear('operator-login:127.0.0.1');

    $this->post('/login', ['key' => 'test-operator-key'])->assertRedirect('/vouchers');
});

it('clears the limiter on a successful sign-in', function () {
    foreach (range(1, 4) as $i) {
        $this->post('/login', ['key' => 'wrong']);
    }

    $this->post('/login', ['key' => 'test-operator-key'])->assertRedirect('/vouchers');

    expect(RateLimiter::attempts('operator-login:127.0.0.1'))->toBe(0);
});
