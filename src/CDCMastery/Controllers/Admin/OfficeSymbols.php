<?php

namespace CDCMastery\Controllers\Admin;

use CDCMastery\Controllers\Admin;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\OfficeSymbols\OfficeSymbol;
use CDCMastery\Models\OfficeSymbols\OfficeSymbolCollection;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

class OfficeSymbols extends Admin
{
    /**
     * @var OfficeSymbolCollection
     */
    private $office_symbols;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        OfficeSymbolCollection $office_symbols
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);

        $this->office_symbols = $office_symbols;
    }

    private function get_osymbol(string $uuid): ?OfficeSymbol
    {
        $osymbol = $this->office_symbols->fetch($uuid);

        if ($osymbol === null || $osymbol->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified Office Symbol does not exist');

            $this->redirect('/admin/office-symbols')->send();
            exit;
        }

        return $osymbol;
    }

    public function do_add(?OfficeSymbol $osymbol = null): Response
    {
        $edit = $osymbol !== null;

        $params = [
            'symbol',
        ];

        if (!$this->checkParameters($params)) {
            goto out_return;
        }

        $symbol = $this->filter_string_default('symbol');

        if (!$edit) {
            $osymbol = new OfficeSymbol();
            $osymbol->setUuid(UUID::generate());
        }

        $osymbol->setSymbol($symbol);

        $db_symbols = $this->office_symbols->fetchAll();
        foreach ($db_symbols as $db_symbol) {
            if ($edit && $db_symbol->getUuid() === $osymbol->getUuid()) {
                continue;
            }

            if ($db_symbol->getSymbol() === $symbol) {
                $this->flash()->add(MessageTypes::ERROR,
                                    "The specified Office Symbol '{$osymbol->getSymbol()}' already exists in the database");
                goto out_return;
            }
        }

        $this->office_symbols->save($osymbol);

        $this->flash()->add(MessageTypes::SUCCESS,
                            $edit
                                ? "The specified Office Symbol '{$osymbol->getSymbol()}' was modified successfully"
                                : "The specified Office Symbol '{$osymbol->getSymbol()}' was added to the database");

        out_return:
        return $this->redirect('/admin/office-symbols');
    }

    public function do_delete(string $uuid): Response
    {
        $osymbol = $this->get_osymbol($uuid);

        $this->office_symbols->delete($osymbol);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The specified Office Symbol has been removed from the database'
        );

        return $this->redirect('/admin/office-symbols');
    }

    public function do_edit(string $uuid): Response
    {
        return $this->do_add($this->get_osymbol($uuid));
    }

    public function show_home(): Response
    {
        $osymbols = $this->office_symbols->fetchAll();

        usort(
            $osymbols,
            static function (OfficeSymbol $a, OfficeSymbol $b): int {
                return $a->getSymbol() <=> $b->getSymbol();
            }
        );

        $data = [
            'osymbols' => $osymbols,
        ];

        return $this->render(
            'admin/office-symbols/list.html.twig',
            $data
        );
    }

    public function show_delete(string $uuid): Response
    {
        $osymbol = $this->get_osymbol($uuid);

        $data = [
            'osymbol' => $osymbol,
        ];

        return $this->render(
            "admin/office-symbols/delete.html.twig",
            $data
        );
    }

    public function show_edit(string $uuid): Response
    {
        $osymbol = $this->get_osymbol($uuid);

        $data = [
            'osymbol' => $osymbol,
        ];

        return $this->render(
            "admin/office-symbols/edit.html.twig",
            $data
        );
    }
}