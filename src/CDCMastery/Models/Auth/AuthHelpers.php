<?php
namespace CDCMastery\Models\Auth;

use CDCMastery\Models\Users\Role;
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

    /**
     * @param $password
     * @param $handle
     * @param $email
     * @return array
     */
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

    /**
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function compare(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * @param string $password
     * @return string
     */
    public static function hash(string $password): string
    {
        return password_hash(
            $password,
            PASSWORD_BCRYPT,
            ['cost' => self::PASSWORD_HASH_ROUNDS]
        );
    }

    /**
     * @return bool
     */
    public function assert_logged_in(): bool
    {
        return $this->session->get(self::KEY_AUTH, false);
    }

    /**
     * @param string $role
     * @return bool
     */
    private function assert_role(string $role): bool
    {
        return $this->session->get(self::KEY_ROLE) === $role;
    }

    /**
     * @return bool
     */
    public function assert_admin(): bool
    {
        return $this->assert_role(Role::TYPE_ADMIN);
    }

    /**
     * @return bool
     */
    public function assert_editor(): bool
    {
        return $this->assert_role(Role::TYPE_QUESTION_EDITOR);
    }

    /**
     * @return bool
     */
    public function assert_supervisor(): bool
    {
        return $this->assert_role(Role::TYPE_SUPERVISOR);
    }

    /**
     * @return bool
     */
    public function assert_training_manager(): bool
    {
        return $this->assert_role(Role::TYPE_TRAINING_MANAGER);
    }

    /**
     * @return bool
     */
    public function assert_user(): bool
    {
        return $this->assert_role(Role::TYPE_USER);
    }

    /**
     * @return null|string
     */
    public function get_user_name(): ?string
    {
        return $this->session->get(self::KEY_USER_NAME);
    }

    /**
     * @return null|string
     */
    public function get_user_uuid(): ?string
    {
        return $this->session->get(self::KEY_USER_UUID);
    }

    public function get_redirect(): ?string
    {
        return $this->session->get(self::KEY_REDIRECT);
    }

    public function set_redirect(string $path): ?string
    {
        return $this->session->set(self::KEY_REDIRECT, $path);
    }

    /**
     * @param User $user
     * @param Role|null $role
     */
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
    }

    public function logout_hook(): void
    {
        $this->limiter->destroy();
        $this->session->invalidate();
    }
}