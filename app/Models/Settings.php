<?php
/**
 * Global settings (key-value) stored in the database.
 * Used for the accepted email domains and the "allow any domain" toggle.
 */
class Settings
{
    public static function get(string $key, ?string $default = null): ?string
    {
        $row = Database::fetchOne("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1", [$key]);
        return $row ? (string) $row['setting_value'] : $default;
    }

    public static function set(string $key, string $value): void
    {
        Database::execute(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$key, trim($value)]
        );
    }

    /** Normalized list of accepted domains (without the leading @). */
    public static function domains(): array
    {
        $raw = (string) self::get('accepted_domains', '');
        $out = [];
        foreach (explode(',', $raw) as $d) {
            $d = strtolower(trim($d));
            if ($d === '') {
                continue;
            }
            $d = ltrim($d, '@');
            if ($d !== '' && strpos($d, '.') !== false) {
                $out[] = $d;
            }
        }
        return array_values(array_unique($out));
    }

    public static function anyDomainAllowed(): bool
    {
        return (string) self::get('allow_any_domain', '0') === '1';
    }

    /** Whether the email uses an accepted domain (or any domain is allowed). */
    public static function emailDomainAllowed(string $email): bool
    {
        if (self::anyDomainAllowed()) {
            return true;
        }
        $at = strrpos($email, '@');
        if ($at === false) {
            return false;
        }
        $domain = strtolower(substr($email, $at + 1));
        return in_array($domain, self::domains(), true);
    }
}