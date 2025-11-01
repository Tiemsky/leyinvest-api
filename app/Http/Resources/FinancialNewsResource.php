<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="FinancialNewsResource",
 *     title="Financial News Resource",
 *     description="Représentation d'une actualité financière provenant de la BRVM ou de RichBourse.",
 *     type="object",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         example=1,
 *         description="Identifiant unique de l'actualité"
 *     ),
 *     @OA\Property(
 *         property="company",
 *         type="string",
 *         example="ECOBANK CI",
 *         description="Nom de la société concernée par l'actualité"
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         example="ECOBANK CÔTE D'IVOIRE : Notation Financière",
 *         description="Titre de l'actualité financière"
 *     ),
 *     @OA\Property(
 *         property="pdf_url",
 *         type="string",
 *         format="uri",
 *         example="https://www.brvm.org/files/boc_20251028.pdf",
 *         description="Lien direct vers le document PDF de la publication"
 *     ),
 *     @OA\Property(
 *         property="source",
 *         type="string",
 *         example="brvm_notations",
 *         description="Source de l'information (brvm_notations, richbourse_etats_financiers, etc.)"
 *     ),
 *     @OA\Property(
 *         property="published_at",
 *         type="string",
 *         format="date",
 *         example="2025-10-28",
 *         description="Date de publication officielle de l'actualité"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-10-29T10:00:00.000000Z",
 *         description="Date et heure d'enregistrement dans la base de données"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         example="2025-10-29T10:05:00.000000Z",
 *         description="Dernière date de mise à jour de l'enregistrement"
 *     )
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
        return [
            'id'           => $this->id,
            'company'      => $this->company,
            'title'        => $this->title,
            'pdf_url'      => $this->pdf_url,
            'source'       => $this->source,
            'published_at' => $this->published_at?->toDateString(),
            'created_at'   => $this->created_at?->toISOString(),
            'updated_at'   => $this->updated_at?->toISOString(),
        ];
    }
}
