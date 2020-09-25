<?php


namespace CDCMastery\Models\Email\Templates;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\Activation\Activation;
use CDCMastery\Models\Email\Email;
use CDCMastery\Models\Users\User;

class ActivateAccount
{
    public static function email(User $initiator, User $tgt_user, Activation $activation): Email
    {
        $expires_fmt = $activation->getDateExpires()->format(DateTimeHelpers::DT_FMT_LONG);
        $body = <<<TXT
{$tgt_user->getName()},

Thank you for registering an account at CDCMastery! To confirm your e-mail
address and activate your account, click the link below:

  https://cdcmastery.com/auth/activate/{$activation->getCode()}

This request expires on {$expires_fmt}.

If you cannot click on the link, copy and paste the link into your browser.

Regards,

CDCMastery Support
TXT;

        $email = new Email();
        $email->setUuid(UUID::generate());
        $email->setSubject('Account Activation - CDCMastery');
        $email->setBodyHtml('<html lang="en"><body>' . nl2br($body) . '</body></html>');
        $email->setBodyTxt($body);
        $email->setRecipient($tgt_user->getEmail());
        $email->setSender(SUPPORT_EMAIL);
        $email->setUserUuid($initiator->getUuid());

        return $email;
    }
}