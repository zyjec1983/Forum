<?php
/**
 * Manejador de vistas MVC.
 */
class View
{
    protected static function resolve(string $view): string
    {
        $file = APP_PATH . DS . 'Views' . DS . str_replace('.', DS, $view) . '.php';
        if (!is_file($file)) {
            http_response_code(500);
            exit('View not found: ' . e($view));
        }
        return $file;
    }

    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require self::resolve($view);
    }

    public static function renderPartial(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require self::resolve($view);
        return (string) ob_get_clean();
    }
}