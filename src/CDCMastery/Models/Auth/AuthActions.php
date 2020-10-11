<?php
declare(strict_types=1);


namespace CDCMastery\Models\Auth;


use CDCMastery\Controllers\RootController;
use CDCMastery\Models\Auth\Activation\Activation;
use CDCMastery\Models\Auth\Activation\ActivationCollection;
use CDCMastery\Models\Auth\PasswordReset\PasswordReset;
use CDCMastery\Models\Auth\PasswordReset\PasswordResetCollection;
use CDCMastery\Models\Email\EmailCollection;
use CDCMastery\Models\Email\Templates\ActivateAccount;
use CDCMastery\Models\Email\Templates\ResetPassword;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Users\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Throwable;

class AuthActions
{
    private LoggerInterface $log;
    private PasswordResetCollection $pw_resets;
    private ActivationCollection $activations;
    private EmailCollection $emails;

    /**
     * AuthActions constructor.
     * @param LoggerInterface $log
     * @param PasswordResetCollection $pw_resets
     * @param ActivationCollection $activations
     * @param EmailCollection $emails
     */
    public function __construct(
        LoggerInterface $log,
        PasswordResetCollection $pw_resets,
        ActivationCollection $activations,
        EmailCollection $emails
    ) {
        $this->log = $log;
        $this->pw_resets = $pw_resets;
        $this->activations = $activations;
        $this->emails = $emails;
    }

    public function do_resend_activation(
        FlashBagInterface $flash,
        User $user,
        User $initiator,
        string $url_success,
        string $url_failure
    ): Response {
        $activation = Activation::factory($user);
        $email = ActivateAccount::email($initiator, $user, $activation);

        try {
            $this->emails->queue($email);
        } catch (Throwable $e) {
            $this->log->debug($e);
            $flash->add(MessageTypes::ERROR,
                        'The system encountered an error while attempting to resend the activation e-mail');

            return RootController::static_redirect($url_failure);
        }

        $this->activations->save($activation);
        $this->log->info("queue activation :: {$activation->getCode()} :: user {$user->getName()} [{$user->getUuid()}]");
        $this->log->info("resend user activation :: {$user->getName()} [{$user->getUuid()}] :: user {$initiator->getUuid()}");

        $flash->add(MessageTypes::SUCCESS,
                    'An activation request for this user was successfully initiated');

        return RootController::static_redirect($url_success);
    }

    public function do_password_reset(
        FlashBagInterface $flash,
        User $user,
        User $initiator,
        string $url_success,
        string $url_failure
    ): Response {
        $pw_reset = PasswordReset::factory($user);
        $email = ResetPassword::email($initiator, $user, $pw_reset);

        try {
            $this->emails->queue($email);
        } catch (Throwable $e) {
            $this->log->debug($e);
            $flash->add(MessageTypes::ERROR,
                        'The system encountered an error while attempting to send the password reset e-mail');

            return RootController::static_redirect($url_failure);
        }

        $this->pw_resets->save($pw_reset);
        $this->log->info("reset user password :: {$user->getName()} [{$user->getUuid()}] :: user {$initiator->getUuid()}");

        $flash->add(MessageTypes::SUCCESS,
                    'A password reset request for this user was successfully initiated');

        return RootController::static_redirect($url_success);
    }
}
