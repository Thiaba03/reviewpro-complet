<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\DataSource;
use App\Models\ImportBatch;
use App\Models\Product;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class ImportDatafinitiReviews extends Command
{
    protected $signature = 'reviews:import-datafiniti
        {path=storage/app/imports/kaggle/datafiniti/raw/Datafiniti_Amazon_Consumer_Reviews_of_Amazon_Products_May19.csv}
        {--limit= : Nombre maximal de lignes électroniques à traiter}';

    protected $description =
        'Importe et normalise les avis électroniques du dataset Datafiniti';

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        if (! is_file($path)) {
            $this->error("Fichier introuvable : {$path}");

            return self::FAILURE;
        }

        $source = DataSource::where(
            'code',
            'kaggle_datafiniti_amazon_reviews'
        )->first();

        if (! $source) {
            $this->error(
                'Source absente. Exécute : '
                .'php artisan db:seed --class=DataSourcesSeeder'
            );

            return self::FAILURE;
        }

        $limit = $this->option('limit');

        if ($limit !== null && (! ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error("L'option --limit doit être un entier positif.");

            return self::FAILURE;
        }

        $limit = $limit !== null ? (int) $limit : null;

        $batch = ImportBatch::create([
            'data_source_id' => $source->id,
            'original_filename' => basename($path),
            'file_checksum' => hash_file('sha256', $path),
            'status' => 'running',
            'parameters' => [
                'category_filter' => 'Electronics',
                'language' => 'en',
                'limit' => $limit,
                'username_imported' => false,
            ],
            'started_at' => now(),
        ]);

        $handle = null;

        $rowsRead = 0;
        $rowsSkipped = 0;
        $rowsImported = 0;
        $rowsRejected = 0;
        $rowsDuplicated = 0;
        $eligibleProcessed = 0;

        try {
            $handle = fopen($path, 'r');

            if ($handle === false) {
                throw new \RuntimeException(
                    "Impossible d'ouvrir le fichier CSV."
                );
            }

            $header = fgetcsv($handle);

            if ($header === false) {
                throw new \RuntimeException('Le fichier CSV est vide.');
            }

            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);

            $requiredColumns = [
                'id',
                'name',
                'asins',
                'brand',
                'primaryCategories',
                'reviews.date',
                'reviews.id',
                'reviews.rating',
                'reviews.text',
            ];

            $missingColumns = array_diff($requiredColumns, $header);

            if ($missingColumns !== []) {
                throw new \RuntimeException(
                    'Colonnes manquantes : '.implode(', ', $missingColumns)
                );
            }

            while (($values = fgetcsv($handle)) !== false) {
                $rowsRead++;

                if (count($values) !== count($header)) {
                    $rowsRejected++;
                    continue;
                }

                $row = array_combine($header, $values);

                if ($row === false) {
                    $rowsRejected++;
                    continue;
                }

                $primaryCategory = trim(
                    (string) ($row['primaryCategories'] ?? '')
                );

                if (! str_contains(
                    strtolower($primaryCategory),
                    'electronics'
                )) {
                    $rowsSkipped++;
                    continue;
                }

                $eligibleProcessed++;

                $brandName = trim((string) ($row['brand'] ?? ''));
                $productExternalId = trim(
                    (string) ($row['id'] ?: $row['asins'])
                );
                $content = Str::squish(
                    (string) ($row['reviews.text'] ?? '')
                );
                $ratingRaw = trim(
                    (string) ($row['reviews.rating'] ?? '')
                );

                if (
                    $brandName === ''
                    || $productExternalId === ''
                    || $content === ''
                    || $ratingRaw === ''
                    || ! is_numeric($ratingRaw)
                ) {
                    $rowsRejected++;

                    if ($limit !== null && $eligibleProcessed >= $limit) {
                        break;
                    }

                    continue;
                }

                $rating = (float) $ratingRaw;

                if ($rating < 1 || $rating > 5) {
                    $rowsRejected++;

                    if ($limit !== null && $eligibleProcessed >= $limit) {
                        break;
                    }

                    continue;
                }

                $normalizedBrand = match (strtolower($brandName)) {
                    'amazonbasics' => 'AmazonBasics',
                    'amazon' => 'Amazon',
                    default => Str::title($brandName),
                };

                $brand = Brand::firstOrCreate(
                    ['slug' => Str::slug($normalizedBrand)],
                    ['name' => $normalizedBrand]
                );

                $product = Product::updateOrCreate(
                    [
                        'source' => 'datafiniti_amazon',
                        'source_product_id' => $productExternalId,
                    ],
                    [
                        'brand_id' => $brand->id,
                        'name' => trim((string) ($row['name'] ?? '')) ?: null,
                        'category' => $primaryCategory ?: null,
                        'subcategory' => trim(
                            (string) ($row['categories'] ?? '')
                        ) ?: null,
                        'product_url' => trim(
                            (string) ($row['sourceURLs'] ?? '')
                        ) ?: null,
                        'image_url' => trim(
                            (string) ($row['imageURLs'] ?? '')
                        ) ?: null,
                    ]
                );

                $contentHash = hash(
                    'sha256',
                    mb_strtolower($content, 'UTF-8')
                );

                $sourceReviewId = trim(
                    (string) ($row['reviews.id'] ?? '')
                );

                if ($sourceReviewId === '') {
                    $sourceReviewId = hash(
                        'sha256',
                        $productExternalId
                        .'|'.$contentHash
                        .'|'.($row['reviews.date'] ?? '')
                    );
                }

                $alreadyExists = Review::where(
                    'source',
                    'kaggle_datafiniti'
                )
                    ->where('source_review_id', $sourceReviewId)
                    ->exists();

                if ($alreadyExists) {
                    $rowsDuplicated++;

                    if ($limit !== null && $eligibleProcessed >= $limit) {
                        break;
                    }

                    continue;
                }

                $reviewDate = null;

                if (! empty($row['reviews.date'])) {
                    try {
                        $reviewDate = Carbon::parse(
                            $row['reviews.date']
                        );
                    } catch (Throwable) {
                        $reviewDate = null;
                    }
                }

                $sentiment = match (true) {
                    $rating <= 2 => 'negative',
                    $rating == 3 => 'neutral',
                    default => 'positive',
                };

                Review::create([
                    'product_id' => $product->id,
                    'import_batch_id' => $batch->id,
                    'content' => $content,
                    'language' => 'en',
                    'content_hash' => $contentHash,
                    'source' => 'kaggle_datafiniti',
                    'source_review_id' => $sourceReviewId,

                    // Le nom d’utilisateur du CSV n’est pas importé.
                    'auteur' => null,
                    'is_anonymized' => true,

                    'note' => $rating,
                    'date_avis' => $reviewDate,
                    'sentiment' => $sentiment,
                    'score' => (int) (($rating - 1) * 25),
                    'topics' => null,
                ]);

                $rowsImported++;

                if ($rowsRead % 1000 === 0) {
                    $this->info(
                        "{$rowsRead} lignes lues, "
                        ."{$rowsImported} avis importés"
                    );
                }

                if ($limit !== null && $eligibleProcessed >= $limit) {
                    break;
                }
            }

            fclose($handle);
            $handle = null;

            $batch->update([
                'status' => 'completed',
                'rows_read' => $rowsRead,
                'rows_skipped' => $rowsSkipped,
                'rows_imported' => $rowsImported,
                'rows_rejected' => $rowsRejected,
                'rows_duplicated' => $rowsDuplicated,
                'finished_at' => now(),
            ]);

            $this->newLine();
            $this->info("Import terminé — lot #{$batch->id}");
            $this->table(
                ['Mesure', 'Valeur'],
                [
                    ['Lignes lues', $rowsRead],
                    ['Lignes ignorées', $rowsSkipped],
                    ['Lignes importées', $rowsImported],
                    ['Lignes rejetées', $rowsRejected],
                    ['Doublons', $rowsDuplicated],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            $batch->update([
                'status' => 'failed',
                'rows_read' => $rowsRead,
                'rows_skipped' => $rowsSkipped,
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
}