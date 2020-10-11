<?php
declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: tehbi
 * Date: 7/16/2017
 * Time: 4:02 PM
 */

namespace CDCMastery\Models\Twig;


use CDCMastery\Models\Users\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class UserProfileLink extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('profile_link', [$this, 'profile_link']),
        ];
    }

    public function profile_link(User $user): string
    {
        return '<a href="/admin/users/' . $user->getUuid() . '">' . $user->getName() . '</a>';
    }
}
