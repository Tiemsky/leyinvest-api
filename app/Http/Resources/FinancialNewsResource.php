<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
        $isLocalFile = ! Str::startsWith($this->pdf_url, ['http://', 'https://']);

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
        if (! $this->pdf_url) {
            return '';
        }

        if (! $isLocalFile) {
            // Pour les URL distantes (BRVM), l'URL est la même pour les deux options
            return $this->pdf_url;
        }

        // Pour les fichiers locaux (RichBourse), on utilise la route API sécurisée
        return route($routeName, ['document' => $this->key]);
    }
}
