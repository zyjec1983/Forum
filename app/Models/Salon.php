<?php
/**
 * Classrooms model (9th "A", 9th "B", ...). Managed from the admin panel.
 */
class Salon
{
    public static function all(): array
    {
        return Database::fetchAll("SELECT * FROM salones ORDER BY name ASC");
    }

    public static function find(int $id): ?array
    {
        return Database::fetchOne("SELECT * FROM salones WHERE id = ? LIMIT 1", [$id]);
    }

    public static function create(string $name): int
    {
        Database::execute("INSERT INTO salones (name) VALUES (?)", [trim($name)]);
        return Database::insertId();
    }

    public static function delete(int $id): void
    {
        // Users get salon_id NULL (FK SET NULL)
        Database::execute("DELETE FROM salones WHERE id = ?", [$id]);
    }

    public static function count(): int
    {
        return Database::count("SELECT COUNT(*) AS c FROM salones");
    }
}