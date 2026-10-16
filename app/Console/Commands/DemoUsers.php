<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Statamic\Facades\User;

/**
 * The demo's accounts: admin@example.com for the Control Panel, and the
 * journal's three writers, whose names show on its social images. All use
 * the password "password", so this only runs on local sites.
 */
class DemoUsers extends Command
{
    protected $signature = 'demo:users';

    protected $description = 'Make the demo shop\'s admin and writer accounts (local only)';

    public const WRITERS = [
        'mira' => ['mira@example.com', 'Mira Castellane'],
        'tobias' => ['tobias@example.com', 'Tobias Venn'],
        'june' => ['june@example.com', 'June Okoro'],
    ];

    public function handle(): int
    {
        if (! app()->environment('local')) {
            $this->components->error('Demo accounts have a known password, so they\'re only made on local sites.');

            return self::FAILURE;
        }

        $admin = User::findByEmail('admin@example.com') ?? User::make()->email('admin@example.com');
        $admin->set('name', 'Demo Admin')->password('password')->makeSuper()->save();
        $this->components->twoColumnDetail('admin@example.com', 'Control Panel');

        foreach (self::WRITERS as $id => [$email, $name]) {
            $user = User::findByEmail($email) ?? User::make()->id($id)->email($email);
            $user->set('name', $name)->password('password')->save();
            $this->components->twoColumnDetail($email, 'Writer');
        }

        return self::SUCCESS;
    }
}
