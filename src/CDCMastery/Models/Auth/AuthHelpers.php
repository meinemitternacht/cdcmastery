<?php
declare(strict_types=1);

namespace CDCMastery\Models\Auth;

use CDCMastery\Models\Users\Roles\Role;
use CDCMastery\Models\Users\User;
use Psr\Log\LoggerInterface;
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

    private const LEGACY_HASH_ALGO = 'sha1';

    private const PASSWORD_HASH_ROUNDS = 14;
    private const PASSWORD_HASH_OPTIONS = ['cost' => self::PASSWORD_HASH_ROUNDS];

    private Session $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public static function check_complexity(string $password, string $handle, string $email): array
    {
        $errors = [];
        $noLetters = false;

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least eight characters.";
        }

        if (!preg_match("#[\d]+#", $password)) {
            $errors[] = "Password must include at least one number.";
        }

        if (!preg_match("#[a-zA-Z]+#", $password)) {
            $errors[] = "Password must include at least one letter.";
            $noLetters = true;
        }

        if (!preg_match("/[!@#$%^&*()_+`~\-=,.\/<>?;':\"\[\]{}|]+/", $password)) {
            $errors[] = "Password must include at least one symbol.";
        }

        if (!$noLetters && !preg_match("#[A-Z]+#", $password)) {
            $errors[] = "Password must include at least one uppercase letter.";
        }

        if (!$noLetters && !preg_match("#[a-z]+#", $password)) {
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

    public static function check_email(string $email): bool
    {
        return (bool)preg_match('/\.mil$/', $email);
    }

    public static function compare_legacy(string $password, string $hash): bool
    {
        return hash_equals($hash, hash(self::LEGACY_HASH_ALGO, $password));
    }

    public static function compare(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function check_rehash(LoggerInterface $log, User $user, string $password): void
    {
        $old_hash = $user->getPassword();
        if (!$old_hash) {
            return;
        }

        if (password_needs_rehash($old_hash,
                                  PASSWORD_DEFAULT,
                                  self::PASSWORD_HASH_OPTIONS)) {
            $user->setPassword(password_hash($password,
                                             PASSWORD_DEFAULT,
                                             self::PASSWORD_HASH_OPTIONS));
            $log->info("user password rehash :: {$user->getName()} [{$user->getUuid()}]");
        }
    }

    public static function hash(string $password): string
    {
        return password_hash($password,
                             PASSWORD_DEFAULT,
                             self::PASSWORD_HASH_OPTIONS);
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

    public function login_hook(User $user, Role $role): void
    {
        $this->session->migrate();

        $this->session->set(self::KEY_AUTH, true);
        $this->session->set(self::KEY_USER_NAME, $user->getName());
        $this->session->set(self::KEY_USER_UUID, $user->getUuid());

        $this->session->set(self::KEY_ROLE, $role->getType());
        $this->session->set(self::KEY_ROLE_UUID, $role->getUuid());
    }

    public function logout_hook(): void
    {
        $this->session->invalidate();
    }
}