<?php
declare(strict_types=1);

namespace App\Crm;

/**
 * Values of a JSON object the CRM answered, read as the type the portal uses them as.
 */
final readonly class Fields
{
    /**
     * @param array<array-key, mixed> $values
     */
    public function __construct(private array $values)
    {
    }

    /**
     * Text, empty when the CRM sent none.
     */
    public function text(string $name): string
    {
        $value = $this->values[$name] ?? null;

        return is_scalar($value) && !is_bool($value) ? trim((string)$value) : '';
    }

    /**
     * A yes or no.
     *
     * @param string $name The value.
     * @return bool
     */
    public function flag(string $name): bool
    {
        return filter_var($this->values[$name] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * A whole number, nought when the CRM sent none.
     *
     * @param string $name The value.
     * @return int
     */
    public function int(string $name): int
    {
        $value = $this->values[$name] ?? null;

        return is_numeric($value) ? (int)$value : 0;
    }

    /**
     * A number, nought when the CRM sent none.
     *
     * @param string $name The value.
     * @return float
     */
    public function number(string $name): float
    {
        $value = $this->values[$name] ?? null;

        return is_numeric($value) ? (float)$value : 0.0;
    }

    /**
     * A JSON object inside this one, empty when there is none.
     *
     * @param string $name The value.
     * @return self
     */
    public function object(string $name): self
    {
        $value = $this->values[$name] ?? null;

        return new self(is_array($value) ? $value : []);
    }

    /**
     * The objects of a list.
     *
     * @return list<self>
     */
    public function list(string $name): array
    {
        $value = $this->values[$name] ?? null;
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (is_array($item)) {
                $items[] = new self($item);
            }
        }

        return $items;
    }

    /**
     * An object of texts, such as the countries by their id.
     *
     * @return array<array-key, string>
     */
    public function texts(string $name): array
    {
        $value = $this->values[$name] ?? null;
        if (!is_array($value)) {
            return [];
        }

        $texts = [];
        foreach ($value as $key => $text) {
            if (is_scalar($text)) {
                $texts[(string)$key] = (string)$text;
            }
        }

        return $texts;
    }

    /**
     * Whether the CRM sent the value at all.
     *
     * @param string $name The value.
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }
}
