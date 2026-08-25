<?php

namespace App\Console\Commands;

use App\Models\DataSource;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\ProductSnapshot;
use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ScrapeWebScraperLaptops extends Command
{
    protected $signature = 'products:scrape-laptops
        {--pages=1 : Nombre de pages à parcourir, entre 1 et 20}
        {--delay=1 : Délai en secondes entre les pages}';

    protected $description =
        'Collecte les ordinateurs du site pédagogique Web Scraper';

    private const BASE_URL =
        'https://webscraper.io/test-sites/e-commerce/static/computers/laptops';

    public function handle(): int
    {
        $pages = (int) $this->option('pages');
        $delay = (int) $this->option('delay');

        if ($pages < 1 || $pages > 20) {
            $this->error('Le nombre de pages doit être compris entre 1 et 20.');

            return self::FAILURE;
        }

        if ($delay < 1 || $delay > 10) {
            $this->error('Le délai doit être compris entre 1 et 10 secondes.');

            return self::FAILURE;
        }

        $source = DataSource::where(
            'code',
            'webscraper_test_laptops'
        )->first();

        if (! $source) {
            $this->error(
                'Source absente. Exécute le seeder DataSourcesSeeder.'
            );

            return self::FAILURE;
        }

        $batch = ImportBatch::create([
            'data_source_id' => $source->id,
            'status' => 'running',
            'parameters' => [
                'base_url' => self::BASE_URL,
                'pages_requested' => $pages,
                'delay_seconds' => $delay,
                'personal_data_collected' => false,
                'review_texts_collected' => false,
            ],
            'started_at' => now(),
        ]);

        $rowsRead = 0;
        $rowsImported = 0;
        $rowsRejected = 0;
        $rowsDuplicated = 0;

        try {
            for ($page = 1; $page <= $pages; $page++) {
                $url = self::BASE_URL.'?page='.$page;

                $this->info("Lecture de la page {$page} : {$url}");

                $response = Http::timeout(30)
                    ->retry(2, 1000)
                    ->withHeaders([
                        'User-Agent' => implode(' ', [
                            'ReviewPro-RNCP/1.0',
                            '(projet pédagogique;',
                            'collecte limitée;',
                            'aucune donnée personnelle)',
                        ]),
                        'Accept' => 'text/html',
                    ])
                    ->get($url);

                if ($response->failed()) {
                    throw new RuntimeException(
                        "Erreur HTTP {$response->status()} sur {$url}"
                    );
                }

                $products = $this->parseProducts(
                    $response->body(),
                    $url
                );

                if ($products === []) {
                    throw new RuntimeException(
                        "Aucun produit détecté sur la page {$page}."
                    );
                }

                foreach ($products as $item) {
                    $rowsRead++;

                    if (
                        $item['name'] === ''
                        || $item['source_url'] === ''
                        || $item['price'] === null
                    ) {
                        $rowsRejected++;
                        continue;
                    }

                    $externalId = hash(
                        'sha256',
                        $item['source_url']
                    );

                    $product = Product::updateOrCreate(
                        [
                            'source' => 'webscraper_test',
                            'source_product_id' => $externalId,
                        ],
                        [
                            'brand_id' => null,
                            'name' => $item['name'],
                            'category' => 'Electronics',
                            'subcategory' => 'Laptops',
                            'product_url' => $item['source_url'],
                        ]
                    );

                    $existingSnapshot = ProductSnapshot::where([
                        'product_id' => $product->id,
                        'import_batch_id' => $batch->id,
                    ])->exists();

                    if ($existingSnapshot) {
                        $rowsDuplicated++;
                        continue;
                    }

                    ProductSnapshot::create([
                        'product_id' => $product->id,
                        'import_batch_id' => $batch->id,
                        'price' => $item['price'],
                        'currency' => 'USD',
                        'average_rating' => $item['average_rating'],
                        'displayed_review_count' =>
                            $item['displayed_review_count'],
                        'description' => $item['description'],
                        'source_url' => $item['source_url'],
                        'collected_at' => now(),
                    ]);

                    $rowsImported++;
                }

                $this->info(
                    count($products).' produits détectés sur la page.'
                );

                if ($page < $pages) {
                    sleep($delay);
                }
            }

            $batch->update([
                'status' => 'completed',
                'rows_read' => $rowsRead,
                'rows_skipped' => 0,
                'rows_imported' => $rowsImported,
                'rows_rejected' => $rowsRejected,
                'rows_duplicated' => $rowsDuplicated,
                'finished_at' => now(),
            ]);

            $this->newLine();
            $this->info("Scraping terminé — lot #{$batch->id}");

            $this->table(
                ['Mesure', 'Valeur'],
                [
                    ['Pages parcourues', $pages],
                    ['Produits lus', $rowsRead],
                    ['Relevés enregistrés', $rowsImported],
                    ['Produits rejetés', $rowsRejected],
                    ['Doublons', $rowsDuplicated],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $batch->update([
                'status' => 'failed',
                'rows_read' => $rowsRead,
                'rows_skipped' => 0,
                'rows_imported' => $rowsImported,
                'rows_rejected' => $rowsRejected,
                'rows_duplicated' => $rowsDuplicated,
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function parseProducts(
        string $html,
        string $pageUrl
    ): array {
        libxml_use_internal_errors(true);

        $document = new DOMDocument();
        $loaded = $document->loadHTML($html);

        libxml_clear_errors();

        if (! $loaded) {
            throw new RuntimeException('HTML impossible à analyser.');
        }

        $xpath = new DOMXPath($document);

        $cards = $xpath->query(
            "//div[contains(concat(' ', normalize-space(@class), ' '), ' thumbnail ')]"
        );

        $products = [];

        foreach ($cards as $card) {
            $titleNode = $xpath->query(
                ".//a[contains(concat(' ', normalize-space(@class), ' '), ' title ')]",
                $card
            )->item(0);

            $priceNode = $xpath->query(
                ".//*[contains(concat(' ', normalize-space(@class), ' '), ' price ')]",
                $card
            )->item(0);

            $descriptionNode = $xpath->query(
                ".//p[contains(concat(' ', normalize-space(@class), ' '), ' description ')]",
                $card
            )->item(0);

            $reviewNode = $xpath->query(
                ".//p[contains(concat(' ', normalize-space(@class), ' '), ' review-count ')]",
                $card
            )->item(0);

            $ratingNode = $xpath->query(
                ".//*[@data-rating]",
                $card
            )->item(0);

            $name = trim($titleNode?->textContent ?? '');
            $href = trim(
                $titleNode?->attributes?->getNamedItem('href')?->nodeValue
                ?? ''
            );

            $sourceUrl = $this->absoluteUrl($href, $pageUrl);

            $priceText = trim($priceNode?->textContent ?? '');
            $price = $this->parsePrice($priceText);

            $reviewText = trim($reviewNode?->textContent ?? '');

            preg_match('/(\d+)/', $reviewText, $reviewMatches);

            $ratingRaw = $ratingNode
                ?->attributes
                ?->getNamedItem('data-rating')
                ?->nodeValue;

            $products[] = [
                'name' => $name,
                'price' => $price,
                'description' => Str::squish(
                    $descriptionNode?->textContent ?? ''
                ),
                'average_rating' => is_numeric($ratingRaw)
                    ? (float) $ratingRaw
                    : null,
                'displayed_review_count' =>
                    isset($reviewMatches[1])
                        ? (int) $reviewMatches[1]
                        : null,
                'source_url' => $sourceUrl,
            ];
        }

        return $products;
    }

    private function parsePrice(string $value): ?float
    {
        $normalized = preg_replace(
            '/[^0-9.,]/',
            '',
            $value
        );

        $normalized = str_replace(',', '.', $normalized ?? '');

        return is_numeric($normalized)
            ? (float) $normalized
            : null;
    }

    private function absoluteUrl(
        string $href,
        string $pageUrl
    ): string {
        if ($href === '') {
            return '';
        }

        if (str_starts_with($href, 'http')) {
            return $href;
        }

        $parts = parse_url($pageUrl);

        return ($parts['scheme'] ?? 'https')
            .'://'
            .($parts['host'] ?? 'webscraper.io')
            .'/'
            .ltrim($href, '/');
    }
}