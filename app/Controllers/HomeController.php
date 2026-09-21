<?php
/**
 * Root page controller.
 * index.php must redirect to the view that captures student data
 * (registration) when the user is not signed in yet.
 */
class HomeController extends Controller
{
    public function index(): void
    {
        if (is_logged()) {
            redirect(base_url('forum'));
        }
        // Security: any unauthenticated access goes to data capture (registration)
        redirect(base_url('auth/login'));
    }
}