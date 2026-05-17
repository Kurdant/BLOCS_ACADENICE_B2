<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Validateur de formulaires serveur.
 *
 * Accumule les erreurs de validation sans lever d'exception.
 * Chaque règle retourne $this pour permettre le chaînage.
 *
 * Usage :
 *   $v = new Validator();
 *   $v->required('nom', $nom, 'Nom')
 *     ->maxLength('nom', $nom, 100, 'Nom')
 *     ->positiveNumber('prix', $prix, 'Prix');
 *
 *   if ($v->fails()) {
 *       $this->flash('error', $v->firstError());
 *       $this->redirect('/formulaire');
 *       return;
 *   }
 */
final class Validator
{
    /**
     * Tableau des erreurs accumulées : ['champ' => 'message', ...]
     *
     * @var array<string, string>
     */
    private array $errors = [];

    /**
     * Vérifie qu'une valeur n'est pas vide (après trim).
     * Ajoute une erreur si la valeur est une chaîne vide ou uniquement des espaces.
     */
    public function required(string $field, mixed $value, string $label): self
    {
        // Ne pas écraser une erreur déjà enregistrée sur ce champ
        if (isset($this->errors[$field])) {
            return $this;
        }

        if (!is_string($value) || trim($value) === '') {
            $this->errors[$field] = "Le champ « {$label} » est obligatoire.";
        }

        return $this;
    }

    /**
     * Vérifie que la longueur d'une chaîne ne dépasse pas $max caractères.
     */
    public function maxLength(string $field, string $value, int $max, string $label): self
    {
        if (isset($this->errors[$field])) {
            return $this;
        }

        if (mb_strlen($value) > $max) {
            $this->errors[$field] = "Le champ « {$label} » ne doit pas dépasser {$max} caractères.";
        }

        return $this;
    }

    /**
     * Vérifie que la longueur d'une chaîne est au moins $min caractères.
     */
    public function minLength(string $field, string $value, int $min, string $label): self
    {
        if (isset($this->errors[$field])) {
            return $this;
        }

        if (mb_strlen(trim($value)) < $min) {
            $this->errors[$field] = "Le champ « {$label} » doit contenir au moins {$min} caractères.";
        }

        return $this;
    }

    /**
     * Vérifie qu'une valeur est un nombre strictement positif (> 0).
     * Accepte les entiers, les flottants et les chaînes numériques.
     */
    public function positiveNumber(string $field, mixed $value, string $label): self
    {
        if (isset($this->errors[$field])) {
            return $this;
        }

        // is_numeric accepte "12.5", "12", 12, 12.5
        if (!is_numeric($value) || (float) $value <= 0) {
            $this->errors[$field] = "Le champ « {$label} » doit être un nombre supérieur à 0.";
        }

        return $this;
    }

    /**
     * Vérifie qu'une valeur est un nombre positif ou nul (>= 0).
     * Utilisé pour les suppléments de prix (qui peuvent être à 0).
     */
    public function nonNegativeNumber(string $field, mixed $value, string $label): self
    {
        if (isset($this->errors[$field])) {
            return $this;
        }

        if (!is_numeric($value) || (float) $value < 0) {
            $this->errors[$field] = "Le champ « {$label} » doit être un nombre positif ou nul.";
        }

        return $this;
    }

    /**
     * Vérifie qu'une valeur entière est comprise entre $min et $max inclus.
     */
    public function intBetween(string $field, mixed $value, int $min, int $max, string $label): self
    {
        if (isset($this->errors[$field])) {
            return $this;
        }

        $int = filter_var($value, FILTER_VALIDATE_INT);
        if ($int === false || $int < $min || $int > $max) {
            $this->errors[$field] = "Le champ « {$label} » doit être un entier entre {$min} et {$max}.";
        }

        return $this;
    }

    /**
     * Vérifie qu'une valeur fait partie d'une liste de valeurs autorisées.
     *
     * @param array<mixed> $allowed
     */
    public function inList(string $field, mixed $value, array $allowed, string $label): self
    {
        if (isset($this->errors[$field])) {
            return $this;
        }

        if (!in_array($value, $allowed, strict: true)) {
            $this->errors[$field] = "La valeur de « {$label} » n'est pas autorisée.";
        }

        return $this;
    }

    /**
     * Retourne true si au moins une erreur a été enregistrée.
     */
    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /**
     * Retourne toutes les erreurs sous forme de tableau [champ => message].
     *
     * @return array<string, string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Retourne le premier message d'erreur enregistré.
     * Pratique pour afficher un flash d'erreur unique quand on n'a qu'un seul message à afficher.
     */
    public function firstError(): string
    {
        return array_values($this->errors)[0] ?? '';
    }
}
