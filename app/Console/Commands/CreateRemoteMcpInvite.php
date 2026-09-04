<?php

namespace App\Console\Commands;

use App\Models\RemoteMcpInvite;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateRemoteMcpInvite extends Command
{
    protected $signature = 'tiktok:mcp:invite {label : Recipient or platform label} {--expires=30 : Days before the invite expires}';

    protected $description = 'Create a revocable invite for the OAuth remote TikTok Shop MCP';

    public function handle(): int
    {
        $plainCode = Str::upper(Str::random(24));
        $days = max(1, min(365, (int) $this->option('expires')));

        RemoteMcpInvite::query()->create([
            'label' => (string) $this->argument('label'),
            'code_hash' => Hash::make($plainCode),
            'expires_at' => now()->addDays($days),
        ]);

        $this->line('Remote MCP invitation created. Share this code only with the intended recipient:');
        $this->line($plainCode);

        return self::SUCCESS;
    }
}
