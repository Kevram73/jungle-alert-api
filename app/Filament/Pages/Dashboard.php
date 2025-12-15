<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\StatsOverview;
use App\Filament\Widgets\SubscriptionsChart;
use App\Filament\Widgets\UsersChart;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected function getWidgets(): array
    {
        return [
            StatsOverview::class,
            UsersChart::class,
            SubscriptionsChart::class,
        ];
    }
}

