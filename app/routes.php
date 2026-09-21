<?php
/**
 * Application routes definition.
 * @var Router $router
 */

// Root: redirects to data capture (registration) or to the forum
$router->get('/', [HomeController::class, 'index']);

// Authentication
$router->get('auth/register', [AuthController::class, 'showRegister']);
$router->post('auth/register', [AuthController::class, 'register']);
$router->get('auth/login', [AuthController::class, 'showLogin']);
$router->post('auth/login', [AuthController::class, 'login']);
$router->get('auth/recover', [AuthController::class, 'showRecover']);
$router->post('auth/recover', [AuthController::class, 'recover']);
$router->get('auth/logout', [AuthController::class, 'logout']);

// Forum (student)
$router->get('forum', [ForumController::class, 'show']);
$router->post('forum/respond-teacher', [ForumController::class, 'respondTeacher']);
$router->post('forum/respond-partner', [ForumController::class, 'respondPartner']);
$router->post('forum/conclusion', [ForumController::class, 'saveConclusion']);
$router->post('forum/report', [ForumController::class, 'reportSecurity']);

// Admin panel
$router->get('admin', [AdminController::class, 'dashboard']);
$router->get('admin/forum', [AdminController::class, 'forumIndex']);
$router->post('admin/forum/create', [AdminController::class, 'forumCreate']);
$router->post('admin/forum/edit', [AdminController::class, 'forumEdit']);
$router->post('admin/forum/activate', [AdminController::class, 'forumActivate']);
$router->post('admin/forum/reopen', [AdminController::class, 'forumReopen']);
$router->get('admin/salones', [AdminController::class, 'salones']);
$router->post('admin/salones/save', [AdminController::class, 'salonesSave']);
$router->post('admin/salones/delete', [AdminController::class, 'salonesDelete']);
$router->get('admin/students', [AdminController::class, 'students']);
$router->post('admin/students/save', [AdminController::class, 'studentSave']);
$router->post('admin/students/toggle', [AdminController::class, 'studentToggle']);
$router->get('admin/logs', [AdminController::class, 'logs']);
$router->get('admin/responses', [AdminController::class, 'responses']);