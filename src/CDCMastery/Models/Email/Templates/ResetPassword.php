<?php
declare(strict_types=1);


namespace CDCMastery\Models\Email\Templates;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\PasswordReset\PasswordReset;
use CDCMastery\Models\Email\Email;
use CDCMastery\Models\Users\User;

class ResetPassword
{
    public static function email(User $initiator, User $tgt_user, PasswordReset $reset): Email
    {
        $expires_fmt = $reset->getDateExpires()->format(DateTimeHelpers::DT_FMT_LONG);
        $body = <<<TXT
{$tgt_user->getName()},

You, or someone pretending to be you, requested a password reset from 
CDCMastery.  If you did not initiate this request, ignore this e-mail
and message us via Facebook:

  https://www.facebook.com/CDCMastery/

To continue with the password reset request, click the following link
and follow the instructions provided:

  https://cdcmastery.com/auth/reset/{$reset->getUuid()}

This request expires on {$expires_fmt}.

Regards,

CDCMastery Support
TXT;

        $email = new Email();
        $email->setUuid(UUID::generate());
        $email->setSubject('Password Reset - CDCMastery');
        $email->setBodyHtml('<html lang="en"><body>' . nl2br($body) . '</body></html>');
        $email->setBodyTxt($body);
        $email->setRecipient($tgt_user->getEmail());
        $email->setSender(SUPPORT_EMAIL);
        $email->setUserUuid($initiator->getUuid());

        return $email;
    }
}
