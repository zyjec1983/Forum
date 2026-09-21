<?php
/**
 * Base controller.
 */
abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }
}