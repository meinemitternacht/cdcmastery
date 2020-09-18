<?php


namespace CDCMastery\Controllers\Admin;


use CDCMastery\Controllers\Admin;
use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\FlashCards\Card;
use CDCMastery\Models\FlashCards\CardCollection;
use CDCMastery\Models\FlashCards\CardHelpers;
use CDCMastery\Models\FlashCards\Category;
use CDCMastery\Models\FlashCards\CategoryCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Sorting\Cards\CardCategorySortOption;
use CDCMastery\Models\Sorting\ISortOption;
use CDCMastery\Models\Users\UserCollection;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Environment;

class FlashCards extends Admin
{
    private CategoryCollection $categories;
    private CardCollection $cards;
    private AfscCollection $afscs;
    private UserCollection $users;
    private CdcDataCollection $cdc_data;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        AuthHelpers $auth_helpers,
        CategoryCollection $categories,
        CardCollection $cards,
        AfscCollection $afscs,
        UserCollection $users,
        CdcDataCollection $cdc_data
    ) {
        parent::__construct($logger, $twig, $session, $auth_helpers);

        $this->categories = $categories;
        $this->cards = $cards;
        $this->afscs = $afscs;
        $this->users = $users;
        $this->cdc_data = $cdc_data;
    }

    private function validate_sort(string $column, string $direction): ?ISortOption
    {
        try {
            return new CardCategorySortOption($column,
                                              strtolower($direction ?? 'asc') === 'asc'
                                                  ? ISortOption::SORT_ASC
                                                  : ISortOption::SORT_DESC);
        } catch (Throwable $e) {
            unset($e);
            return null;
        }
    }

    public function do_card_add(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $params = [
            'card_front',
            'card_back',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/cards/{$cat->getUuid()}/data/add");
        }

        $front = $this->get('card_front');
        $back = $this->get('card_back');

        $card = new Card();
        $card->setUuid(UUID::generate());
        $card->setFront($front);
        $card->setBack($back);
        $card->setCategory($cat->getUuid());

        $this->cards->save($cat, $card);
        $this->flash()->add(MessageTypes::SUCCESS,
                            'The flash card was saved successfully');

        return $this->redirect("/admin/cards/{$cat->getUuid()}/data/add");
    }

    public function do_card_delete(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card || $card->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card could not be found');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $this->cards->delete($card);
        $this->flash()->add(MessageTypes::SUCCESS,
                            'The flash card was removed successfully');

        return $this->redirect("/admin/cards/{$cat->getUuid()}");
    }

    public function do_card_edit(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card || $card->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card could not be found');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $params = [
            'card_front',
            'card_back',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $front = $this->get('card_front');
        $back = $this->get('card_back');

        $card->setFront($front);
        $card->setBack($back);

        $this->cards->save($cat, $card);
        $this->flash()->add(MessageTypes::SUCCESS,
                            'The flash card was saved successfully');

        return $this->redirect("/admin/cards/{$cat->getUuid()}");
    }

    public function do_category_add(?Category $cat = null, ?Afsc $afsc = null): Response
    {
        $edit = $cat !== null;

        if ($afsc) {
            if (!$edit) {
                $cat = new Category();
                $cat->setUuid(UUID::generate());
                $cat->setCreatedBy($this->auth_helpers->get_user_uuid());
                $cat->setName($afsc->getName());
                $cat->setBinding($afsc->getUuid());
                $cat->setEncrypted($afsc->isFouo()); // only set when initially creating category
                $cat->setType(Category::TYPE_AFSC);
            }

            $cat->setComments($this->get('comments') ?? $afsc->getVersion());

            goto out_save;
        }

        $params = [
            'name',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/cards/add");
        }

        $name = $this->filter_string_default('name');
        $encrypted = $this->filter_bool_default('encrypted', false);
        $binding = $this->filter_string_default('binding');
        $comments = $this->get('comments');

        if ($binding) {
            $bind_afsc = $this->afscs->fetch($binding);

            if (!$bind_afsc || $bind_afsc->getUuid() === '') {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The specified AFSC does not exist'
                );

                return $this->redirect("/admin/cards/add");
            }

            if ($bind_afsc->isFouo()) {
                $encrypted = true;
            }
        }

        if (!$edit) {
            $cat = new Category();
            $cat->setUuid(UUID::generate());
            $cat->setCreatedBy($this->auth_helpers->get_user_uuid());
            $cat->setEncrypted($encrypted); // only set when initially creating category
            $cat->setType(Category::TYPE_GLOBAL);
        }

        $cat->setName($name);
        $cat->setBinding($binding);
        $cat->setComments($comments);

        out_save:
        $this->categories->save($cat);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The flash card category has been ' . ($edit
                ? 'edited'
                : 'added') . ' successfully'
        );

        return $this->redirect("/admin/cards/{$cat->getUuid()}");
    }

    public function do_category_add_afsc(): Response
    {
        $params = [
            'tgt_afsc',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/admin/cards/add-afsc");
        }

        $tgt_afsc = $this->filter_string_default('tgt_afsc');

        $afsc = $this->afscs->fetch($tgt_afsc);

        if (!$afsc || $afsc->getUuid() === '') {
            $this->flash()->add(
                MessageTypes::ERROR,
                'The specified AFSC does not exist'
            );

            return $this->redirect("/admin/cards/add-afsc");
        }

        if ($this->categories->fetchAfsc($afsc) !== null) {
            $this->flash()->add(
                MessageTypes::ERROR,
                'A flash card category for that AFSC already exists'
            );

            return $this->redirect("/admin/cards/add-afsc");
        }

        return $this->do_category_add(null, $afsc);
    }

    public function do_category_edit(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        if ($cat->getBinding()) {
            $afsc = $this->afscs->fetch($cat->getBinding());

            if (!$afsc || $afsc->getUuid() === '') {
                $this->flash()->add(
                    MessageTypes::ERROR,
                    'The AFSC for this category no longer exists'
                );

                return $this->redirect("/admin/cards/add");
            }

            return $this->do_category_add($cat, $afsc);
        }

        return $this->do_category_add($cat);
    }

    public function do_category_delete(string $uuid): Response
    {
        if (!CDC_DEBUG) {
            throw new RuntimeException('This endpoint cannot be accessed when debug mode is disabled');
        }

        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        $this->categories->delete($uuid);

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The specified flash card category has been removed successfully'
        );

        return $this->redirect('/admin/cards');
    }

    public function show_card(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card does not exist');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $user = $this->users->fetch($cat->getCreatedBy());

        $data = [
            'cat' => $cat,
            'card' => $card,
            'user' => $user,
        ];

        return $this->render(
            'admin/cards/card.html.twig',
            $data
        );
    }

    public function show_card_add(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $user = $this->users->fetch($cat->getCreatedBy());

        $afsc = $cat->getType() === 'afsc'
            ? $this->afscs->fetch($cat->getBinding())
            : null;

        $data = [
            'cat' => $cat,
            'user' => $user,
            'afsc' => $afsc,
        ];

        return $this->render(
            'admin/cards/card-new.html.twig',
            $data
        );
    }

    public function show_card_edit(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $data = [
            'cat' => $cat,
            'card' => $card,
        ];

        return $this->render(
            'admin/cards/card-edit.html.twig',
            $data
        );
    }

    public function show_card_delete(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect("/admin/cards/{$cat->getUuid()}");
        }

        $data = [
            'cat' => $cat,
            'card' => $card,
        ];

        return $this->render(
            'admin/cards/card-delete.html.twig',
            $data
        );
    }

    public function show_category(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        $binding = $cat->getBinding();
        $afsc = $binding
            ? $this->afscs->fetch($binding)
            : null;

        switch ($cat->getType()) {
            case Category::TYPE_AFSC:
                if (!$afsc || $afsc->getUuid() === '') {
                    $this->flash()->add(
                        MessageTypes::WARNING,
                        'The AFSC for that category no longer exists'
                    );

                    return $this->redirect('/admin/cards');
                }

                $cards = CardHelpers::create_afsc_cards($this->cdc_data, $afsc);
                break;
            case Category::TYPE_GLOBAL:
            case Category::TYPE_PRIVATE:
            default:
                $cards = $this->cards->fetchCategory($cat);
                break;
        }

        $user = $this->users->fetch($cat->getCreatedBy());

        $data = [
            'cat' => $cat,
            'cards' => $cards,
            'user' => $user,
            'afsc' => $afsc,
        ];

        return $this->render(
            'admin/cards/category.html.twig',
            $data
        );
    }

    public function show_category_add(bool $from_afsc = false): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());
        $tgt_afscs = $this->afscs->fetchAll(AfscCollection::SHOW_ALL);

        if ($from_afsc) {
            $afsc_uuids = array_map(static function (Category $v): string {
                return $v->getBinding();
            }, $this->categories->filterAfsc());

            $tgt_afscs = array_diff_key($tgt_afscs, array_flip(array_filter($afsc_uuids)));
        }

        $data = [
            'user' => $user,
            'afscs' => $tgt_afscs,
        ];

        return $this->render(
            $from_afsc
                ? 'admin/cards/category-new-afsc.html.twig'
                : 'admin/cards/category-new.html.twig',
            $data
        );
    }

    public function show_category_add_afsc(): Response
    {
        return $this->show_category_add(true);
    }

    public function show_category_edit(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        $data = [
            'cat' => $cat,
        ];

        return $this->render(
            'admin/cards/category-edit.html.twig',
            $data
        );
    }

    public function show_category_delete(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/admin/cards');
        }

        $data = [
            'cat' => $cat,
        ];

        return $this->render(
            'admin/cards/category-delete.html.twig',
            $data
        );
    }

    public function show_home(): Response
    {
        $sortCol = $this->get(ArrayPaginator::VAR_SORT);
        $sortDir = $this->get(ArrayPaginator::VAR_DIRECTION);
        $curPage = $this->get(ArrayPaginator::VAR_START, ArrayPaginator::DEFAULT_START);
        $numRecords = $this->get(ArrayPaginator::VAR_ROWS, ArrayPaginator::DEFAULT_ROWS);

        $sort = $sortCol
            ? [$this->validate_sort($sortCol, $sortDir)]
            : [
                new CardCategorySortOption(CardCategorySortOption::COL_NAME),
            ];

        $sort[] = new CardCategorySortOption(CardCategorySortOption::COL_UUID);

        $n_cats = $this->categories->count();

        if (!$n_cats) {
            $this->flash()->add(MessageTypes::WARNING,
                                'There are no flash card categories in the database');

            return $this->redirect('/');
        }

        $cats = $this->categories->fetchAll($sort, $curPage * $numRecords, $numRecords);

        $user_uuids = [];
        foreach ($cats as $cat) {
            $user_uuids[ $cat->getCreatedBy() ] = true;
        }

        $users = $this->users->fetchArray(array_keys($user_uuids));

        $pagination = ArrayPaginator::buildLinks(
            '/admin/cards',
            $curPage,
            ArrayPaginator::calcNumPagesNoData(
                $n_cats,
                $numRecords
            ),
            $numRecords,
            $sortCol,
            $sortDir
        );

        $data = [
            'users' => $users,
            'cats' => $cats,
            'n_cats' => $n_cats,
            'pagination' => $pagination,
            'sort' => [
                'col' => $sortCol,
                'dir' => $sortDir,
            ],
        ];

        return $this->render(
            'admin/cards/list.html.twig',
            $data
        );
    }
}