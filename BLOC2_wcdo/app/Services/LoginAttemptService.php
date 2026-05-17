<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Gestion des tentatives de connexion échouées — protection force brute.
 *
 * Stockage : fichier JSON dans /tmp, identifié par sha256(identifiant|ip).
 * Seuil    : 5 échecs dans une fenêtre de 15 minutes → blocage 15 minutes.
 * Délai    : 3 secondes imposées après chaque échec de mot de passe.
 */
final class LoginAttemptService
{
    private const MAX_ATTEMPTS  = 5;
    private const WINDOW        = 900;   // 15 minutes en secondes
    private const LOCK_DURATION = 900;   // 15 minutes en secondes
    private const FAIL_DELAY    = 3;     // délai minimal après échec (secondes)

    private string $filePath;

    public function __construct(string $identifiant, string $ip)
    {
        $key = hash('sha256', $identifiant . '|' . $ip);
        $this->filePath = sys_get_temp_dir() . '/wacdo_login_' . $key . '.json';
    }

    /**
     * Indique si l'accès est bloqué pour cette combinaison identifiant/IP.
     * Supprime automatiquement le fichier si le délai de blocage est écoulé.
     */
    public function isLocked(): bool
    {
        $data = $this->read();

        if ($data === null || $data['locked_at'] === null) {
            return false;
        }

        if (time() - $data['locked_at'] < self::LOCK_DURATION) {
            return true;
        }

        // Délai écoulé : réinitialisation automatique
        $this->delete();
        return false;
    }

    /**
     * Enregistre un échec d'authentification.
     * Déclenche le blocage à la 5ème tentative.
     * Applique un délai de 3 secondes (CDC 6.3.1).
     */
    public function recordFailure(): void
    {
        $now = time();

        // Lecture + écriture atomique via flock pour éviter la race condition
        // si deux requêtes échouent simultanément pour le même identifiant/IP
        $fp = fopen($this->filePath, 'c+');
        if ($fp === false) {
            sleep(self::FAIL_DELAY);
            return;
        }

        flock($fp, LOCK_EX);

        $content = stream_get_contents($fp);
        $data    = (is_string($content) && $content !== '')
            ? json_decode($content, true)
            : null;

        if (!is_array($data)) {
            $data = ['attempts' => 0, 'first_attempt' => $now, 'locked_at' => null];
        }

        // Réinitialiser si la fenêtre de 15 min est expirée (sans blocage actif)
        if ($data['locked_at'] === null && ($now - $data['first_attempt']) > self::WINDOW) {
            $data = ['attempts' => 0, 'first_attempt' => $now, 'locked_at' => null];
        }

        $data['attempts']++;

        if ($data['attempts'] >= self::MAX_ATTEMPTS) {
            $data['locked_at'] = $now;
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string) json_encode($data));
        flock($fp, LOCK_UN);
        fclose($fp);

        // Délai minimal de 3 secondes après chaque échec (CDC 6.3.1)
        sleep(self::FAIL_DELAY);
    }

    /**
     * Supprime le fichier de tentatives après une connexion réussie.
     */
    public function recordSuccess(): void
    {
        $this->delete();
    }

    // -------------------------------------------------------------------------
    // Helpers privés
    // -------------------------------------------------------------------------

    private function read(): ?array
    {
        if (!is_file($this->filePath)) {
            return null;
        }

        $content = file_get_contents($this->filePath);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        return is_array($data) ? $data : null;
    }

    private function write(array $data): void
    {
        file_put_contents($this->filePath, json_encode($data), LOCK_EX);
    }

    private function delete(): void
    {
        if (is_file($this->filePath)) {
            unlink($this->filePath);
        }
    }
}
