<?php
declare(strict_types=1);

namespace App\Portal;

use Cake\Core\Configure;

/**
 * The operator's look and texts: a directory the deployment mounts at /app/profile, with what it
 * leaves out taken from profiles/default.
 *
 * profile.php returns the values below (or the part that differs), consent.php is what the
 * customer is told about the consents, and assets/ holds the files profile.php names.
 */
final readonly class Profile
{
    /**
     * The files of assets/ the portal serves, and their types.
     */
    public const ASSET_TYPES = [
        'css' => 'text/css; charset=UTF-8',
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
    ];

    /**
     * @param string $directory The profile.
     * @param string $defaultDirectory profiles/default.
     * @param array<string, mixed> $values profile.php of both.
     */
    public function __construct(
        private string $directory,
        private string $defaultDirectory,
        private array $values,
    ) {
    }

    /**
     * The profile the configuration points at.
     *
     * @param string $operator The operator's name, which the default profile uses.
     */
    public static function fromConfig(string $operator): self
    {
        $directory = (string)Configure::read('Portal.profile');
        $defaultDirectory = (string)Configure::read('Portal.defaultProfile');
        // profile.php sees $operator
        $load = static function (string $file) use ($operator): array {
            $values = require $file;

            return is_array($values) ? $values : [];
        };
        $values = $load($defaultDirectory . DS . 'profile.php');
        if ($directory !== $defaultDirectory) {
            $values = array_replace($values, $load($directory . DS . 'profile.php'));
        }

        $named = [];
        foreach ($values as $key => $value) {
            $named[(string)$key] = $value;
        }

        return new self($directory, $defaultDirectory, $named);
    }

    /**
     * The name of the pages, in the title of a browser window.
     */
    public function pageName(): string
    {
        return $this->text('page_name');
    }

    /**
     * What the portal is called on its pages.
     */
    public function heading(): string
    {
        return $this->text('heading');
    }

    /**
     * The logo in assets/, for a dark background.
     */
    public function logo(): string
    {
        $logo = $this->values['logo'] ?? null;
        if (is_array($logo)) {
            $logo = $logo['file'] ?? null;
        }

        return is_string($logo) ? $logo : '';
    }

    /**
     * The stylesheet in assets/ with the operator's colours.
     */
    public function colors(): string
    {
        return $this->text('colors');
    }

    /**
     * The icons of the site, for the head of a page.
     *
     * @return list<array{rel: string, file: string, sizes: string, type: string}>
     */
    public function icons(): array
    {
        $icons = [];
        $values = $this->values['icons'] ?? [];
        foreach (is_array($values) ? $values : [] as $icon) {
            if (!is_array($icon) || !is_string($icon['file'] ?? null) || $icon['file'] === '') {
                continue;
            }
            $file = $icon['file'];
            $icons[] = [
                'rel' => is_string($icon['rel'] ?? null) ? $icon['rel'] : 'icon',
                'file' => $file,
                'sizes' => is_string($icon['sizes'] ?? null) ? $icon['sizes'] : '',
                'type' => self::ASSET_TYPES[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? '',
            ];
        }

        return $icons;
    }

    /**
     * The name a downloaded invoice is saved under.
     */
    public function invoiceFile(string $number, string $variableSymbol): string
    {
        $name = strtr($this->text('invoice_file'), ['{number}' => $number, '{variable_symbol}' => $variableSymbol]);

        return str_replace(['/', '\\'], '-', $name);
    }

    /**
     * A text part of the profile, consent.php, from the profile when it has one.
     */
    public function part(string $name): string
    {
        $file = $this->directory . DS . $name . '.php';

        return is_file($file) ? $file : $this->defaultDirectory . DS . $name . '.php';
    }

    /**
     * A file of assets/: only a plain name of a known type, from the profile or the default.
     *
     * @return string|null The path, or null when there is no such file to serve.
     */
    public function asset(string $file): ?string
    {
        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*$/', $file) || self::assetType($file) === null) {
            return null;
        }
        foreach ([$this->directory, $this->defaultDirectory] as $directory) {
            $path = $directory . DS . 'assets' . DS . $file;
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * The type a file of assets/ is served as, or null when it is not served.
     *
     * @param string $file The file.
     * @return string|null
     */
    public static function assetType(string $file): ?string
    {
        return self::ASSET_TYPES[strtolower(pathinfo($file, PATHINFO_EXTENSION))] ?? null;
    }

    /**
     * A text value of profile.php, empty when there is none.
     *
     * @param string $key The value.
     * @return string
     */
    private function text(string $key): string
    {
        $value = $this->values[$key] ?? null;

        return is_string($value) ? $value : '';
    }
}
