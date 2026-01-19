<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FinancialNews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    /**
     * Sert le fichier PDF stocké localement ou redirige/sert l'URL distante,
     * en fonction du nom de la route (download ou view).
     *
     * @param  FinancialNews  $document  (Route Model Binding par 'key')
     */
    public function servePdf(Request $request, FinancialNews $document)
    {
        // 1. Récupération du chemin/URL
        $filePath = $document->pdf_url;
        if (empty($filePath)) {
            return response()->json(['message' => 'Lien PDF non disponible.'], 404);
        }

        // 2. Cas n°1 : Le fichier est distant (BRVM)
        if (filter_var($filePath, FILTER_VALIDATE_URL)) {
            // On redirige vers l'URL d'origine. Le navigateur distant gérera l'ouverture/téléchargement.
            return redirect()->to($filePath);
        }

        // 3. Cas n°2 : Le fichier est local (RichBourse)
        $disk = 'local';

        if (! Storage::disk($disk)->exists($filePath)) {
            \Log::error('❌ Fichier local introuvable', ['key' => $document->key, 'path' => $filePath]);

            return response()->json(['message' => 'Fichier introuvable sur le serveur.'], 404);
        }

        // 4. Détermination du mode (Download vs View)
        // Si on est sur la route 'api.documents.download', le mode est 'attachment' (téléchargement)
        $disposition = $request->routeIs('api.documents.download') ? 'attachment' : 'inline';

        // 5. Préparation du nom de fichier
        // On s'assure que le nom du fichier est propre pour le client (évite les noms avec hash)
        $filenameForClient = Str::slug($document->company.' '.$document->title).'.pdf';

        // 6. Définition des headers
        $headers = [
            'Content-Type' => 'application/pdf',
            // Le header Content-Disposition est la clé qui force le mode
            'Content-Disposition' => $disposition.'; filename="'.$filenameForClient.'"',
        ];

        // 7. Servir le fichier local avec les headers appropriés
        return Storage::disk($disk)->response($filePath, null, $headers);
    }
}
