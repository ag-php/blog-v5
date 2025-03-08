<?php

use App\Models\User;

use function Pest\Laravel\assertGuest;

use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertAuthenticated;

it('creates a new user and redirects to intended URL', function () {
    $provider = Mockery::mock(GithubProvider::class);
    $provider->shouldReceive('user')->andReturn(new class
    {
        public function getEmail()
        {
            return 'test@example.com';
        }

        public function getName()
        {
            return 'Test User';
        }

        public function getNickname()
        {
            return 'testuser';
        }
    });

    Socialite::shouldReceive('driver')
        ->with('github')
        ->andReturn($provider);

    // Set the intended URL this way to make the test pass. Using from() doesn't work.
    session()->put('url.intended', route('posts.index'));

    assertGuest()
        ->get(route('auth.callback'))
        ->assertRedirect(route('posts.index'))
        ->assertSessionHas('status', 'You have been logged in.');

    assertAuthenticated();

    assertDatabaseHas(User::class, [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'github_login' => 'testuser',
    ]);
});

it('updates an existing user and redirects to intended URL', function () {
    $provider = Mockery::mock(GithubProvider::class);
    $provider->shouldReceive('user')->andReturn(new class
    {
        public function getEmail()
        {
            return 'test@example.com';
        }

        public function getName()
        {
            return 'New Name';
        }

        public function getNickname()
        {
            return 'newusername';
        }
    });

    Socialite::shouldReceive('driver')
        ->with('github')
        ->andReturn($provider);

    $user = User::factory()->create([
        'email' => 'test@example.com',
    ]);

    // Set the intended URL this way to
    session()->put('url.intended', route('posts.index'));

    assertGuest()
        ->get(route('auth.callback'))
        ->assertRedirect(route('posts.index'))
        ->assertSessionHas('status', 'You have been logged in.');

    assertAuthenticated();

    $user->refresh();

    expect($user->email)->toBe('test@example.com');
    expect($user->name)->toBe('New Name');
    expect($user->github_login)->toBe('newusername');
});
