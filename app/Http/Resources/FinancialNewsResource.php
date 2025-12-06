<?php

namespace App\Http\Resources;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


/**
 * @OA\Schema(
 *     schema="FinancialNews",
 *     type="object",
 *     title="Financial News",
 *     description="Modèle d'actualité financière",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="key", type="string", example="BRVM_2024_001"),
 *     @OA\Property(property="company", type="string", example="SONATEL"),
 *     @OA\Property(property="title", type="string", example="Résultats annuels 2024"),
 *     @OA\Property(property="pdf_url", type="string", example="https://example.com/document.pdf"),
 *     @OA\Property(property="source", type="string", example="BRVM"),
 *     @OA\Property(property="published_at", type="string", format="date", example="2024-12-01"),
 *     @OA\Property(property="published_at_human", type="string", example="il y a 2 jours"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */


class FinancialNewsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Détermine si pdf_url est un chemin local (commence par http/https ?)
        $isLocalFile = !Str::startsWith($this->pdf_url, ['http://', 'https://']);

        return [
            'key' => $this->key,
            'societe' => $this->company,
            'titre' => $this->title,
            'source' => $this->source,

            // 🚨 Lien pour TÉLÉCHARGER DIRECTEMENT (mode attachment)
            'pdf_download_url' => $this->getDownloadLink($isLocalFile, 'api.documents.download'),

            // 🚨 Lien pour OUVRIR DANS LE NAVIGATEUR (mode inline)
            'pdf_view_url' => $this->getDownloadLink($isLocalFile, 'api.documents.view'),

            'date_de_publication' => $this->published_at?->format('Y-m-d'),
            'age_publication' => $this->published_at?->diffForHumans(),
        ];
    }

    /**
     * Génère le lien de téléchargement actionnable par le front-end.
     */
    protected function getDownloadLink(bool $isLocalFile, string $routeName): string
    {
        if (!$this->pdf_url) {
            return '';
        }

        if (!$isLocalFile) {
            // Pour les URL distantes (BRVM), l'URL est la même pour les deux options
            return $this->pdf_url;
        }

        // Pour les fichiers locaux (RichBourse), on utilise la route API sécurisée
        return route($routeName, ['document' => $this->key]);
    }
}
