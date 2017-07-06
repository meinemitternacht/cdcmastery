<?php
namespace CDCMastery\Models\Auth;

use CDCMastery\Helpers\SessionHelpers;
use CDCMastery\Models\Users\Role;
use CDCMastery\Models\Users\User;


/**
 * Class AuthHelpers
 * @package CDCMastery\Models\Auth
 */
class AuthHelpers
{
    const CLASS_ADMIN = 'admin';
    const CLASS_SUPERVISOR = 'supervisor';
    const CLASS_TRNG_MGR = 'training_manager';

    const KEY_AUTH = 'cdcmastery-auth';
    const KEY_CLASS = 'user_class';
    const KEY_REDIRECT = 'login-redirect';

    const PASSWORD_HASH_ROUNDS = 13;

    /**
     * @param $password
     * @param $handle
     * @param $email
     * @return array
     */
    public static function checkPasswordComplexity(
        string $password,
        string $handle,
        string $email
    ): array {
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
    public static function comparePassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * @param string $password
     * @return string
     */
    public static function hashUserPassword(string $password): string
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
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION[self::KEY_AUTH]);
    }

    /**
     * @return bool
     */
    public static function isAdmin(): bool
    {
        if (!isset($_SESSION[self::KEY_CLASS])) {
            return false;
        }

        return $_SESSION[self::KEY_CLASS] === Role::TYPE_ADMIN;
    }

    /**
     * @return bool
     */
    public static function isQuestionEditor(): bool
    {
        if (!isset($_SESSION[self::KEY_CLASS])) {
            return false;
        }

        return $_SESSION[self::KEY_CLASS] === Role::TYPE_QUESTION_EDITOR;
    }

    /**
     * @return bool
     */
    public static function isSupervisor(): bool
    {
        if (!isset($_SESSION[self::KEY_CLASS])) {
            return false;
        }

        return $_SESSION[self::KEY_CLASS] === Role::TYPE_SUPERVISOR;
    }

    /**
     * @return bool
     */
    public static function isTrainingManager(): bool
    {
        if (!isset($_SESSION[self::KEY_CLASS])) {
            return false;
        }

        return $_SESSION[self::KEY_CLASS] === Role::TYPE_TRAINING_MANAGER;
    }

    /**
     * @return bool
     */
    public static function isUser(): bool
    {
        if (!isset($_SESSION[self::KEY_CLASS])) {
            return false;
        }

        return $_SESSION[self::KEY_CLASS] === Role::TYPE_USER;
    }

    /**
     * @param User $user
     * @param Role $role
     */
    public static function postprocessLogin(User $user, Role $role): void
    {
        LoginRateLimiter::reset();
        session_regenerate_id();

        $_SESSION[self::KEY_AUTH] = true;
        $_SESSION[SessionHelpers::KEY_USER_UUID] = $user->getUuid();
        $_SESSION[SessionHelpers::KEY_USER_NAME] = $user->getName();

        if (empty($role->getUuid())) {
            return;
        }

        $_SESSION[self::KEY_CLASS] = $role->getType();
    }

    public static function postprocessLogout(): void
    {
        LoginRateLimiter::reset();
        session_destroy();
    }
}