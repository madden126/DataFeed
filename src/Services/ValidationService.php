<?php

declare(strict_types=1);

namespace App\Services;

use App\Interfaces\Services\ValidationServiceInterface;
use RuntimeException;

class ValidationService implements ValidationServiceInterface
{

    /**
     * @throws RuntimeException
     */
    public function validateData(array $product): array
    {
        $this->validateInput($product);
        return $this->sanitizeData($product);
    }

    public function validateColumnNumber(array $data, int $expectedColumnCount): bool
    {
        return count($data) === $expectedColumnCount;
    }

    /**
     * @throws RuntimeException
     */
    private function validateInput(array $product): void
    {
        $requiredFields = ['gtin', 'language', 'title', 'picture', 'description', 'price', 'stock'];

        foreach ($requiredFields as $field) {
            if (!isset($product[$field]) || trim($product[$field]) === '') {
                throw new RuntimeException("Missing or empty required field: {$field}");
            }
        }

        if (!is_numeric($product['price']) || $product['price'] < 0) {
            throw new RuntimeException("Invalid price value");
        }

        if (!is_numeric($product['stock']) || $product['stock'] < 0) {
            throw new RuntimeException("Invalid stock value");
        }

        if (!is_numeric($product['gtin']) || strlen($product['gtin']) !== 13) {
            throw new RuntimeException("Invalid GTIN format");
        }
    }

    /**
     * @throws RuntimeException
     */
    private function sanitizeData(array $row): array
    {
        return [
            'gtin' => $this->sanitizeGtin($row['gtin']),
            'language' => $this->sanitizeLanguage($row['language']),
            'title' => $this->sanitizeText($row['title'], 255),
            'picture' => $this->sanitizeUrl($row['picture']),
            'description' => $this->sanitizeText($row['description']),
            'price' => $this->sanitizePrice($row['price']),
            'stock' => $this->sanitizeInteger($row['stock'])
        ];
    }

    /**
     * @throws RuntimeException
     */
    private function sanitizeGtin(string $gtin): string
    {
        // Remove any non-numeric characters and ensure 13 digits
        $gtin = preg_replace('/[^0-9]/', '', $gtin);
        if (strlen($gtin) !== 13) {
            throw new RuntimeException("Invalid GTIN format: must be 13 digits");
        }
        return $gtin;
    }

    /**
     * @throws RuntimeException
     */
    private function sanitizeLanguage(string $language): string
    {
        // Ensure 2-letter language code
        $language = strtolower(trim($language));
        if (!preg_match('/^[a-z]{2}$/', $language)) {
            throw new RuntimeException("Invalid language code: must be 2 letters");
        }
        return $language;
    }

    private function sanitizeText(string $text, ?int $maxLength = null): string
    {
        // Remove any potentially harmful HTML/PHP tags
        $text = strip_tags($text);
        // Convert special characters to HTML entities
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Trim whitespace
        $text = trim($text);
        // Truncate if maxLength is specified
        if ($maxLength !== null && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
        }
        return $text;
    }

    /**
     * @throws RuntimeException
     */
    private function sanitizeUrl(string $url): string
    {
        // Basic URL validation and sanitization
        $url = filter_var(trim($url), FILTER_SANITIZE_URL);
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException("Invalid URL format");
        }
        return $url;
    }

    /**
     * @throws RuntimeException
     */
    private function sanitizePrice(string $price): float
    {
        // Remove any non-numeric characters except decimal point
        $price = preg_replace('/[^0-9.]/', '', $price);
        if (!is_numeric($price) || $price < 0) {
            throw new RuntimeException("Invalid price format");
        }
        return round((float)$price, 2);
    }

    /**
     * @throws RuntimeException
     */
    private function sanitizeInteger(string $value): int
    {
        // Remove any non-numeric characters
        $value = preg_replace('/[^0-9-]/', '', $value);
        if (!is_numeric($value) || $value < 0) {
            throw new RuntimeException("Invalid integer format");
        }
        return (int)$value;
    }

}
