<?php

namespace CDCMastery\Controllers\Admin;

use CDCMastery\Controllers\Admin;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Bases\Base;
use CDCMastery\Models\Bases\BaseCollection;
use CDCMastery\Models\Messages\MessageTypes;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class Bases extends Admin
{
    /**
     * @var BaseCollection
     */
    private $bases;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        BaseCollection $bases
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);

        $this->bases = $bases;
    }

    private function get_base(string $uuid): ?Base
    {
        $base = $this->bases->fetch($uuid);

        if ($base === null || $base->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified Base does not exist');

            $this->redirect('/admin/bases')->send();
            exit;
        }

        return $base;
    }

    public function do_add(?Base $base = null): Response
    {
        $edit = $base !== null;

        $params = [
            'name',
        ];

        if (!$this->checkParameters($params)) {
            goto out_return;
        }

        $name = $this->filter_string_default('name');

        if (!$edit) {
            $base = new Base();
            $base->setUuid(UUID::generate());
        }

        $base->setName($name);

        $db_bases = $this->bases->fetchAll();
        foreach ($db_bases as $db_base) {
            if ($edit && $db_base->getUuid() === $base->getUuid()) {
                continue;
            }

            if ($db_base->getName() === $name) {
                $this->flash()->add(MessageTypes::ERROR,
                                    "The specified Base '{$base->getName()}' already exists in the database");
                goto out_return;
            }
        }

        $this->bases->save($base);

        $this->flash()->add(MessageTypes::SUCCESS,
                            $edit
                                ? "The specified Base '{$base->getName()}' was modified successfully"
                                : "The specified Base '{$base->getName()}' was added to the database");

        out_return:
        return $this->redirect('/admin/bases');
    }

    public function do_edit(string $uuid): Response
    {
        return $this->do_add($this->get_base($uuid));
    }

    public function show_home(): Response
    {
        $bases = $this->bases->fetchAll();

        usort(
            $bases,
            static function (Base $a, Base $b): int {
                return $a->getName() <=> $b->getName();
            }
        );

        $data = [
            'bases' => $bases,
        ];

        return $this->render(
            'admin/bases/list.html.twig',
            $data
        );
    }

    public function show_edit(string $uuid): Response
    {
        $base = $this->get_base($uuid);

        $data = [
            'base' => $base,
        ];

        return $this->render(
            "admin/bases/edit.html.twig",
            $data
        );
    }
}