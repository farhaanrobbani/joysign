<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentSignature;
use App\Models\User;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Dashboard admin (full-page Livewire): grid statistik yang disegarkan berkala
 * via wire:poll sehingga angka mengikuti aktivitas terkini tanpa reload.
 */
#[Layout('layouts.admin')]
class DashboardPage extends Component
{
    public function render()
    {
        $stats = [
            'users' => User::query()->count(),
            'documents_total' => Document::query()->count(),
            'documents_draft' => Document::query()->where('status', 'draft')->count(),
            'documents_signed' => Document::query()->where('status', 'signed')->count(),
            'documents_revoked' => Document::query()->where('status', 'revoked')->count(),
            'signatures' => DocumentSignature::query()->count(),
            'audit_logs_24h' => AuditLog::query()->where('created_at', '>=', now()->subDay())->count(),
        ];

        return view('livewire.admin.dashboard-page', compact('stats'))
            ->layout('layouts.admin', [
                'header' => new HtmlString(
                    '<h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">'
                    .__('Admin Dashboard').'</h2>'
                ),
            ]);
    }
}
