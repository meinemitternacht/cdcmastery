<?php
declare(strict_types=1);

namespace CDCMastery\Models\Auth;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Class LoginRateLimiter
 * @package CDCMastery\Models\Auth
 */
class LoginRateLimiter
{
    private const RATE_LIMIT_DURATION = 300;
    private const RATE_LIMIT_THRESHOLD = 5;

    private const KEY_ATTEMPTS = 'rate-limit-attempts';
    private const KEY_INIT_TIME = 'rate-limit-init-time';

    private Session $session;
    /** @var resource */
    private $lock;

    public function __construct(Session $session)
    {
        $this->session = $session;
        $this->lock = sem_get(ftok(__FILE__, 'R'));
        $this->init();
    }

    private function lock(): void
    {
        sem_acquire($this->lock);
    }

    private function unlock(): void
    {
        sem_release($this->lock);
    }

    private function init(): void
    {
        try {
            $this->lock();

            if ($this->session->has(self::KEY_INIT_TIME) &&
                $this->session->has(self::KEY_ATTEMPTS)) {
                return;
            }

            $this->session->set(self::KEY_INIT_TIME, time());
            $this->session->set(self::KEY_ATTEMPTS, 0);
        } finally {
            $this->unlock();
        }
    }

    public function assert_limited(): bool
    {
        try {
            $this->lock();

            $start_time = $this->session->get(self::KEY_INIT_TIME);
            $count = $this->session->get(self::KEY_ATTEMPTS);

            if ($start_time === null || $count === null) {
                $this->destroy_locked();
                return false;
            }

            if ($start_time + self::RATE_LIMIT_DURATION < time()) {
                $this->destroy_locked();
                return false;
            }

            if ($count < self::RATE_LIMIT_THRESHOLD) {
                return false;
            }

            return true;
        } finally {
            $this->unlock();
        }
    }

    public function get_limit_expires_seconds(): int
    {
        try {
            $this->lock();
            $start_time = $this->session->get(self::KEY_INIT_TIME);

            if (!$start_time) {
                return 0;
            }

            return ($start_time + self::RATE_LIMIT_DURATION) - time();
        } finally {
            $this->unlock();
        }
    }

    public function increment(): void
    {
        try {
            $this->lock();
            $this->session->set(self::KEY_ATTEMPTS,
                                $this->session->get(self::KEY_ATTEMPTS, 0) + 1);
        } finally {
            $this->unlock();
        }
    }

    private function destroy_locked(): void
    {
        $this->session->remove(self::KEY_ATTEMPTS);
        $this->session->remove(self::KEY_INIT_TIME);
    }

    public function destroy(): void
    {
        try {
            $this->lock();
            $this->destroy_locked();
        } finally {
            $this->unlock();
        }
    }
}
