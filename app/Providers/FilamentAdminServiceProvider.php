<?php

namespace App\Providers;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Illuminate\Support\ServiceProvider;

class FilamentAdminServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                NavigationGroup::make()
                    ->label('Utilisateurs')
                    ->icon('heroicon-o-users'),
                NavigationGroup::make()
                    ->label('Produits')
                    ->icon('heroicon-o-shopping-bag'),
                NavigationGroup::make()
                    ->label('Alertes')
                    ->icon('heroicon-o-bell'),
                NavigationGroup::make()
                    ->label('Abonnements')
                    ->icon('heroicon-o-credit-card'),
                NavigationGroup::make()
                    ->label('Analytiques')
                    ->icon('heroicon-o-chart-bar'),
            ]);
        });
    }
}
