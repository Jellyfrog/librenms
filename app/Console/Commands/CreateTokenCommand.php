<?php

namespace App\Console\Commands;

use App\Console\LnmsCommand;
use App\Models\User;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CreateTokenCommand extends LnmsCommand
{
    protected $name = 'token:create';

    private const VALID_SCOPES = ['read'];

    public function __construct()
    {
        parent::__construct();

        $this->addArgument('username', InputArgument::OPTIONAL, 'The username to create a token for');
        $this->addOption('scope', 's', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Token scopes (read)', ['read']);
        $this->addOption('name', null, InputOption::VALUE_REQUIRED, 'Token name', 'API Token');
    }

    public function handle(): int
    {
        $username = $this->argument('username');

        if (! $username) {
            $username = $this->ask('Username');
            if (! $username) {
                $this->error('Username is required.');

                return 1;
            }
        }

        $user = User::where('username', $username)->first();
        if (! $user) {
            $this->error("User '$username' not found.");

            return 1;
        }

        $scopes = $this->option('scope');
        $invalidScopes = array_diff($scopes, self::VALID_SCOPES);
        if (! empty($invalidScopes)) {
            $this->error('Invalid scopes: ' . implode(', ', $invalidScopes));
            $this->info('Valid scopes: ' . implode(', ', self::VALID_SCOPES));

            return 1;
        }

        $name = $this->option('name');
        $token = $user->createToken($name, $scopes);

        $this->info('Token created successfully.');
        $this->line('');
        $this->line('<comment>Token:</comment> ' . $token->plainTextToken);
        $this->line('<comment>Name:</comment> ' . $name);
        $this->line('<comment>Scopes:</comment> ' . implode(', ', $scopes));
        $this->line('');
        $this->warn('This token will not be shown again. Please copy it now.');

        return 0;
    }
}
