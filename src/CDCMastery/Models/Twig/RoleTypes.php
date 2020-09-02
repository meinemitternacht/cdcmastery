<?php

namespace CDCMastery\Models\Twig;

use CDCMastery\Models\Users\Role;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RoleTypes extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('role_is_admin', static function (Role $v): bool {
                return $v->getType() === Role::TYPE_ADMIN;
            }),
            new TwigFunction('role_is_training_manager', static function (Role $v): bool {
                return $v->getType() === Role::TYPE_TRAINING_MANAGER;
            }),
            new TwigFunction('role_is_supervisor', static function (Role $v): bool {
                return $v->getType() === Role::TYPE_SUPERVISOR;
            }),
            new TwigFunction('role_is_question_editor', static function (Role $v): bool {
                return $v->getType() === Role::TYPE_QUESTION_EDITOR;
            }),
            new TwigFunction('role_is_user', static function (Role $v): bool {
                return $v->getType() === Role::TYPE_USER;
            }),
        ];
    }
}