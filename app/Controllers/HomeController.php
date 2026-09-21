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
            redirect_after_login();
        }
        // Security: any unauthenticated access goes to the sign-in form
        redirect(base_url('auth/login'));
    }
}