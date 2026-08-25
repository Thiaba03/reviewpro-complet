<?php

namespace App\Console\Commands;

use App\Models\Commerce;
use App\Models\Review;
use App\Services\ReviewAnalyzer;
use Illuminate\Console\Command;

/**
 * Import des avis Trustpilot collectes manuellement.
 *
 * Pourquoi manuel : Trustpilot est protege par un systeme anti-bot (AWS WAF,
 * "Verifying your connection...") qui bloque les requetes automatisees simples
 * (code HTTP 403). Contourner cette protection demanderait un navigateur
 * automatise capable de resoudre un challenge JavaScript, sans garantie de
 * succes. Face a cette contrainte reelle, la collecte de ce sous-ensemble
 * d'avis se fait manuellement : copier/coller depuis le site public dans un
 * CSV, puis import structure via cette commande.
 *
 * Usage :
 *   php artisan reviews:import-manual storage/app/imports/trustpilot_manual_reviews.csv
 */
class ImportManualReviews extends Command
{
    protected $signature = 'reviews:import-manual {csv_path=storage/app/imports/trustpilot_manual_reviews.csv}';

    protected $description = 'Importe des avis Trustpilot collectes manuellement depuis un fichier CSV';

    public function handle(ReviewAnalyzer $analyzer): int
    {
        $path = base_path($this->argument('csv_path'));

        if (! file_exists($path)) {
            $this->error("Fichier introuvable : {$path}");
            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        $header = fgetcsv($handle); // commerce_nom,auteur,texte,note,date_avis

        $inserted = 0;
        $skippedNoCommerce = 0;
        $skippedDuplicate = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($header, $row);

            $commerce = Commerce::where('nom', trim($data['commerce_nom']))->first();
            if (! $commerce) {
                $this->warn("Commerce introuvable, ligne ignoree : {$data['commerce_nom']}");
                $skippedNoCommerce++;
                continue;
            }

            $sourceReviewId = 'manual_' . md5($data['commerce_nom'] . $data['auteur'] . $data['date_avis']);

            $exists = Review::where('source', 'trustpilot')
                ->where('source_review_id', $sourceReviewId)
                ->exists();

            if ($exists) {
                $skippedDuplicate++;
                continue;
            }

            // On passe le texte dans le moteur d'analyse (BC02) au moment de l'import
            $analysis = $analyzer->analyze($data['texte']);

            Review::create([
                'commerce_id' => $commerce->id,
                'content' => $data['texte'],
                'source' => 'trustpilot',
                'source_review_id' => $sourceReviewId,
                'auteur' => $data['auteur'],
                'note' => $data['note'] !== '' ? (float) $data['note'] : null,
                'date_avis' => $data['date_avis'] !== '' ? $data['date_avis'] : null,
                'sentiment' => $analysis['sentiment'],
                'score' => $analysis['score'],
                'topics' => $analysis['topics'],
            ]);

            $inserted++;
        }

        fclose($handle);

        $this->info("Termine : {$inserted} avis importes, {$skippedDuplicate} doublons ignores, {$skippedNoCommerce} lignes ignorees (commerce introuvable)");

        return self::SUCCESS;
    }
}
