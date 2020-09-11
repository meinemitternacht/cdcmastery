<?php
namespace CDCMastery\Models\Auth;

use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\User;
use Symfony\Component\HttpFoundation\Session\Session;


/**
 * Class AuthHelpers
 * @package CDCMastery\Models\Auth
 */
class AuthHelpers
{
    private const KEY_AUTH = 'cdcmastery-auth';
    private const KEY_ROLE = 'user-role';
    private const KEY_ROLE_UUID = 'user-role-uuid';
    private const KEY_REDIRECT = 'login-redirect';
    private const KEY_USER_NAME = 'name';
    private const KEY_USER_UUID = 'uuid';

    private const PASSWORD_HASH_ROUNDS = 13;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var LoginRateLimiter
     */
    private $limiter;

    public function __construct(Session $session, LoginRateLimiter $limiter)
    {
        $this->session = $session;
        $this->limiter = $limiter;
    }

    public static function check_complexity(string $password, string $handle, string $email): array
    {
        $errors = [];
        $noLetters = false;

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least eight characters.";
        }

        if (!preg_match("#[0-9]+#", $password)) {
            $errors[] = "Password must include at least one number.";
        }

        if (!preg_match("#[a-zA-Z]+#", $password)) {
            $errors[] = "Password must include at least one letter.";
            $noLetters = true;
        }

        if (!preg_match("#[A-Z]+#", $password) && !$noLetters) {
            $errors[] = "Password must include at least one uppercase letter.";
        }

        if (!preg_match("#[a-z]+#", $password) && !$noLetters) {
            $errors[] = "Password must include at least one lowercase letter.";
        }

        if (strtolower($password) === strtolower($handle)) {
            $errors[] = "Password cannot match username.";
        }

        if (strtolower($password) === strtolower($email)) {
            $errors[] = "Password cannot match e-mail address.";
        }

        return $errors;
    }

    public static function compare(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function hash(string $password): string
    {
        return password_hash(
            $password,
            PASSWORD_BCRYPT,
            ['cost' => self::PASSWORD_HASH_ROUNDS]
        );
    }

    public function assert_logged_in(): bool
    {
        return $this->session->get(self::KEY_AUTH, false);
    }

    private function assert_role(string $role): bool
    {
        return $this->session->get(self::KEY_ROLE) === $role;
    }

    public function assert_admin(): bool
    {
        return $this->assert_role(Role::TYPE_ADMIN) || $this->assert_role(Role::TYPE_SUPER_ADMIN);
    }

    public function assert_editor(): bool
    {
        return $this->assert_role(Role::TYPE_QUESTION_EDITOR);
    }

    public function assert_supervisor(): bool
    {
        return $this->assert_role(Role::TYPE_SUPERVISOR);
    }

    public function assert_training_manager(): bool
    {
        return $this->assert_role(Role::TYPE_TRAINING_MANAGER);
    }

    public function assert_user(): bool
    {
        return $this->assert_role(Role::TYPE_USER);
    }

    public function get_user_name(): ?string
    {
        return $this->session->get(self::KEY_USER_NAME);
    }

    public function get_user_uuid(): ?string
    {
        return $this->session->get(self::KEY_USER_UUID);
    }

    public function get_redirect(): ?string
    {
        return $this->session->get(self::KEY_REDIRECT);
    }

    public function get_role_type(): ?string
    {
        return $this->session->get(self::KEY_ROLE);
    }

    public function get_role_uuid(): ?string
    {
        return $this->session->get(self::KEY_ROLE_UUID);
    }

    public function set_redirect(string $path): void
    {
        $this->session->set(self::KEY_REDIRECT, $path);
    }

    public function login_hook(User $user, ?Role $role): void
    {
        $this->limiter->destroy();
        $this->session->migrate();

        $this->session->set(self::KEY_AUTH, true);
        $this->session->set(self::KEY_USER_NAME, $user->getName());
        $this->session->set(self::KEY_USER_UUID, $user->getUuid());

        if ($role->getUuid() === null || $role->getUuid() === '') {
            return;
        }

        $this->session->set(self::KEY_ROLE, $role->getType());
        $this->session->set(self::KEY_ROLE_UUID, $role->getUuid());
    }

    public function logout_hook(): void
    {
        $this->limiter->destroy();
        $this->session->invalidate();
    }
}