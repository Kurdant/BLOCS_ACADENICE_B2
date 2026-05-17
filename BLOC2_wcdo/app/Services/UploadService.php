<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Gestion sécurisée des uploads d'images catalogue (produits et menus).
 *
 * Règles appliquées (CDC §6.5) :
 *  - Formats acceptés : JPEG et PNG uniquement
 *  - Taille maximale : 2 Mo
 *  - Type MIME vérifié côté serveur via finfo (pas le champ $_FILES['type'] spoofable)
 *  - Nom d'origine ignoré : un nom unique est généré
 *  - Stockage dans /storage/uploads/{sous-dossier}/
 */
final class UploadService
{
    private const MAX_SIZE_BYTES = 2_097_152; // 2 Mo

    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
    ];

    /**
     * Traite un fichier uploadé et retourne le nom de fichier généré (sans chemin absolu).
     *
     * @param array  $file       Entrée $_FILES['champ']
     * @param string $sousDossier  'produits' ou 'menus'
     * @return string             Nom du fichier stocké, ex: "a1b2c3d4.jpg"
     *
     * @throws \RuntimeException Si le fichier est invalide ou que le déplacement échoue
     */
    public function stocker(array $file, string $sousDossier): string
    {
        // 1. Vérifier qu'un fichier est bien arrivé sans erreur
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Aucun fichier reçu ou erreur d\'upload (code : ' . ($file['error'] ?? '?') . ').');
        }

        // Valider le sous-dossier : caractères alphanumériques et tirets uniquement
        if (!preg_match('/\A[a-z0-9_-]+\z/i', $sousDossier)) {
            throw new \RuntimeException('Sous-dossier de destination invalide.');
        }

        // 2. Vérifier la taille
        if ($file['size'] > self::MAX_SIZE_BYTES) {
            throw new \RuntimeException('Image trop lourde. Taille maximale : 2 Mo.');
        }

        // 3. Vérifier le type MIME réel via finfo (pas $_FILES['type'])
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_MIMES[$mime])) {
            throw new \RuntimeException('Format non autorisé. Seuls JPEG et PNG sont acceptés.');
        }

        $extension = self::ALLOWED_MIMES[$mime];

        // 4. Générer un nom de fichier unique (nom d'origine ignoré)
        $nomFichier = bin2hex(random_bytes(16)) . '.' . $extension;

        // 5. Construire le chemin de destination
        $dossierDest = BASE_PATH . '/storage/uploads/' . $sousDossier . '/';

        if (!is_dir($dossierDest)) {
            mkdir($dossierDest, 0755, true);
        }

        $cheminDest = $dossierDest . $nomFichier;

        // 6. Déplacer le fichier depuis le dossier temporaire
        if (!move_uploaded_file($file['tmp_name'], $cheminDest)) {
            throw new \RuntimeException('Échec du stockage du fichier sur le serveur.');
        }

        return $sousDossier . '/' . $nomFichier;
    }

    /**
     * Supprime un ancien fichier image lors d'un remplacement.
     * Ne lève pas d'exception si le fichier est déjà absent.
     *
     * @param string $nomStocke  Valeur stockée en BDD, ex: "produits/a1b2c3d4.jpg"
     */
    public function supprimer(string $nomStocke): void
    {
        if ($nomStocke === '') {
            return;
        }

        // Garde contre path traversal : le nom stocké ne doit pas contenir '..'
        if (str_contains($nomStocke, '..')) {
            return;
        }

        $chemin = BASE_PATH . '/storage/uploads/' . $nomStocke;

        if (is_file($chemin)) {
            unlink($chemin);
        }
    }
}
