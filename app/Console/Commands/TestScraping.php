<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AmazonScrapingService;
use Illuminate\Support\Facades\Log;

class TestScraping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:scraping {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test scraping with a specific URL and show detailed price extraction';

    protected AmazonScrapingService $scrapingService;

    public function __construct(AmazonScrapingService $scrapingService)
    {
        parent::__construct();
        $this->scrapingService = $scrapingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = $this->argument('url');
        
        $this->info("Testing scraping for: {$url}");
        $this->newLine();
        
        // Désactiver le cache pour ce test
        $result = $this->scrapingService->scrapeProduct($url, false);
        
        if (!$result['success']) {
            $this->error("Scraping failed: " . ($result['error'] ?? 'Unknown error'));
            return 1;
        }
        
        $data = $result['data'];
        
        $this->info("✅ Scraping successful!");
        $this->newLine();
        $this->line("Title: " . ($data['title'] ?? 'N/A'));
        $this->line("ASIN: " . ($data['asin'] ?? 'N/A'));
        $this->line("Marketplace: " . ($data['marketplace'] ?? 'N/A'));
        $this->line("Currency: " . ($data['currency'] ?? 'N/A'));
        $this->newLine();
        
        $this->info("💰 Price Information:");
        $this->line("  Price: " . ($data['price'] ?? 'N/A'));
        $this->line("  Current Price: " . ($data['current_price'] ?? 'N/A'));
        $this->line("  Original Price: " . ($data['original_price'] ?? 'N/A'));
        $this->line("  Discount: " . ($data['discount_percentage'] ?? 'N/A') . '%');
        $this->newLine();
        
        // Afficher les logs de débogage
        $this->info("📋 Check logs for detailed extraction process:");
        $this->line("  tail -f storage/logs/laravel.log");
        
        return 0;
    }
}
