<?php


namespace CDCMastery\Controllers;


use CDCMastery\Helpers\ArrayPaginator;
use CDCMastery\Helpers\UUID;
use CDCMastery\Models\Auth\AuthHelpers;
use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\FlashCards\Card;
use CDCMastery\Models\FlashCards\CardCollection;
use CDCMastery\Models\FlashCards\CardHandler;
use CDCMastery\Models\FlashCards\CardHelpers;
use CDCMastery\Models\FlashCards\Category;
use CDCMastery\Models\FlashCards\CategoryCollection;
use CDCMastery\Models\Messages\MessageTypes;
use CDCMastery\Models\Sorting\Cards\CardCategorySortOption;
use CDCMastery\Models\Sorting\ISortOption;
use CDCMastery\Models\Users\User;
use CDCMastery\Models\Users\Associations\Afsc\UserAfscAssociations;
use CDCMastery\Models\Users\UserCollection;
use Exception;
use Monolog\Logger;
use mysqli;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;
use Twig\Environment;

class Cards extends RootController
{
    private mysqli $db;
    private CacheHandler $cache;
    private AuthHelpers $auth_helpers;
    private CategoryCollection $categories;
    private CardCollection $cards;
    private AfscCollection $afscs;
    private UserCollection $users;
    private CdcDataCollection $cdc_data;
    private UserAfscAssociations $afsc_assocs;

    public function __construct(
        Logger $logger,
        Environment $twig,
        Session $session,
        mysqli $db,
        CacheHandler $cache,
        AuthHelpers $auth_helpers,
        CategoryCollection $categories,
        CardCollection $cards,
        AfscCollection $afscs,
        UserCollection $users,
        CdcDataCollection $cdc_data,
        UserAfscAssociations $afsc_assocs
    ) {
        parent::__construct($logger, $twig, $session);

        $this->db = $db;
        $this->cache = $cache;
        $this->auth_helpers = $auth_helpers;
        $this->categories = $categories;
        $this->cards = $cards;
        $this->afscs = $afscs;
        $this->users = $users;
        $this->cdc_data = $cdc_data;
        $this->afsc_assocs = $afsc_assocs;
    }

    private function check_access(User $user, Category $category): void
    {
        $user_uuid = $user->getUuid();
        switch ($category->getType()) {
            case Category::TYPE_PRIVATE:
                if ($category->getCreatedBy() !== $user_uuid &&
                    $category->getBinding() !== $user_uuid) {
                    goto out_access_denied;
                }
                return;
            case Category::TYPE_AFSC:
                if (!$this->afsc_assocs->assertAuthorized($user, null, $category->getBinding())) {
                    goto out_access_denied;
                }
                return;
            case Category::TYPE_GLOBAL:
            default:
                return;
        }

        out_access_denied:
        $this->trigger_request_debug(__METHOD__);
        $this->flash()->add(
            MessageTypes::ERROR,
            'That flash card category is not visible to your account'
        );

        $this->redirect('/cards')->send();
        exit;
    }

    private function check_access_edit(User $user, Category $category): void
    {
        $user_uuid = $user->getUuid();
        switch ($category->getType()) {
            case Category::TYPE_PRIVATE:
                if ($category->getCreatedBy() === $user_uuid ||
                    $category->getBinding() === $user_uuid) {
                    return;
                }
                break;
            case Category::TYPE_AFSC:
            case Category::TYPE_GLOBAL:
            default:
                break;
        }

        $this->trigger_request_debug(__METHOD__);
        $this->flash()->add(
            MessageTypes::ERROR,
            'That flash card category is not visible to your account'
        );

        $this->redirect('/cards')->send();
        exit;
    }

    private function validate_sort(string $column, string $direction): ?ISortOption
    {
        try {
            /** @noinspection DegradedSwitchInspection */
            switch ($column) {
                case CardCategorySortOption::COL_CREATED_BY:
                    return null;
            }

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

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access_edit($user, $cat);

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $params = [
            'card_front',
            'card_back',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/cards/{$cat->getUuid()}/data/add");
        }

        $front = $this->get('card_front');
        $back = $this->get('card_back');

        $card = new Card();
        $card->setUuid(UUID::generate());
        $card->setFront($front);
        $card->setBack($back);
        $card->setCategory($cat->getUuid());

        $this->cards->save($cat, $card);

        $this->log->info("add flash card :: category {$cat->getName()} [{$cat->getUuid()}] :: {$card->getUuid()} :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'The flash card was saved successfully');

        return $this->redirect("/cards/{$cat->getUuid()}/data/add");
    }

    public function do_card_delete(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access_edit($user, $cat);

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card || $card->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card could not be found');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $this->cards->delete($card);

        $this->log->info("delete flash card :: category {$cat->getName()} [{$cat->getUuid()}] :: {$card->getUuid()} :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'The flash card was removed successfully');

        return $this->redirect("/cards/{$cat->getUuid()}");
    }

    public function do_card_edit(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access_edit($user, $cat);

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card || $card->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card could not be found');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $params = [
            'card_front',
            'card_back',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $front = $this->get('card_front');
        $back = $this->get('card_back');

        $card->setFront($front);
        $card->setBack($back);

        $this->cards->save($cat, $card);

        $this->log->info("edit flash card :: category {$cat->getName()} [{$cat->getUuid()}] :: {$card->getUuid()} :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(MessageTypes::SUCCESS,
                            'The flash card was saved successfully');

        return $this->redirect("/cards/{$cat->getUuid()}");
    }

    /**
     * @param string $uuid
     * @return Response
     * @throws Exception
     */
    public function do_card_handler(string $uuid): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'Your user account could not be found');
            goto out_redirect_cards;
        }

        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');
            goto out_redirect_cards;
        }

        $payload = json_decode($this->getRequest()->getContent(), false, 512, JSON_THROW_ON_ERROR);

        if (!$payload || !isset($payload->action)) {
            $this->trigger_request_debug(__METHOD__);
            $this->flash()->add(
                MessageTypes::ERROR,
                'The system encountered an issue while processing your request, please contact the site administrator if the issue persists'
            );
            goto out_redirect_cards;
        }

        try {
            $handler = CardHandler::factory($this->session,
                                            $this->log,
                                            $this->cache,
                                            $this->afscs,
                                            $this->cdc_data,
                                            $this->cards,
                                            $cat,
                                            $user);

            switch ($payload->action) {
                case CardHandler::ACTION_NO_ACTION:
                    break;
                case CardHandler::ACTION_SHUFFLE:
                    $handler->shuffle();
                    break;
                case CardHandler::ACTION_NAV_FIRST:
                    $handler->first();
                    break;
                case CardHandler::ACTION_NAV_PREV:
                    $handler->previous();
                    break;
                case CardHandler::ACTION_NAV_NEXT:
                    $handler->next();
                    break;
                case CardHandler::ACTION_NAV_LAST:
                    $handler->last();
                    break;
                case CardHandler::ACTION_FLIP_CARD:
                    $handler->flip();
                    break;
            }

            return new JsonResponse($handler->display());
        } catch (Throwable $e) {
            $this->trigger_request_debug(__METHOD__);
            $this->flash()->add(
                MessageTypes::ERROR,
                $e->getMessage()
            );
            $this->log->debug($e);
            unset($e);
        }

        out_redirect_cards:
        try {
            return new JsonResponse(['redirect' => '/cards',]);
        } catch (Throwable $e) {
            $this->log->debug($e);
            return new JsonResponse($e->getMessage(), 500);
        }
    }

    public function do_category_add(?Category $cat = null): Response
    {
        $edit = $cat !== null;

        $params = [
            'name',
        ];

        if (!$this->checkParameters($params)) {
            return $this->redirect("/cards/add");
        }

        $name = $this->filter_string_default('name');
        $comments = $this->get('comments');

        if (!$edit) {
            $cat = new Category();
            $cat->setUuid(UUID::generate());
            $cat->setCreatedBy($this->auth_helpers->get_user_uuid());
            $cat->setEncrypted(true); // only set when initially creating category
            $cat->setType(Category::TYPE_PRIVATE);
        }

        $cat->setName($name);
        $cat->setComments($comments);

        $this->categories->save($cat);
        $this->log->info(($edit
                             ? 'edit'
                             : 'add') . " flash card category :: {$cat->getName()} [{$cat->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");


        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The flash card category has been ' . ($edit
                ? 'edited'
                : 'added') . ' successfully'
        );

        return $this->redirect("/cards/{$cat->getUuid()}");
    }

    public function do_category_edit(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        return $this->do_category_add($cat);
    }

    public function do_category_delete(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access_edit($user, $cat);

        $this->categories->delete($uuid);

        $this->log->info("delete flash card category :: {$cat->getName()} [{$cat->getUuid()}] :: user {$this->auth_helpers->get_user_uuid()}");

        $this->flash()->add(
            MessageTypes::SUCCESS,
            'The specified flash card category has been removed successfully'
        );

        return $this->redirect('/cards');
    }

    public function show_card(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access($user, $cat);

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card does not exist');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $user = $this->users->fetch($cat->getCreatedBy());

        $data = [
            'cat' => $cat,
            'card' => $card,
            'user' => $user,
        ];

        return $this->render(
            'cards/card.html.twig',
            $data
        );
    }

    public function show_card_add(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access_edit($user, $cat);

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/cards/{$cat->getUuid()}");
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
            'cards/card-new.html.twig',
            $data
        );
    }

    public function show_card_edit(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access_edit($user, $cat);

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $data = [
            'cat' => $cat,
            'card' => $card,
        ];

        return $this->render(
            'cards/card-edit.html.twig',
            $data
        );
    }

    public function show_card_delete(string $uuid, string $card_uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access_edit($user, $cat);

        if ($cat->getType() === 'afsc') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category does not support custom cards');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $card = $this->cards->fetch($cat, $card_uuid);

        if (!$card) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect("/cards/{$cat->getUuid()}");
        }

        $data = [
            'cat' => $cat,
            'card' => $card,
        ];

        return $this->render(
            'cards/card-delete.html.twig',
            $data
        );
    }

    public function show_category(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access($user, $cat);

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

                    return $this->redirect('/cards');
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
            'cards/category.html.twig',
            $data
        );
    }

    public function show_category_add(): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());
        $tgt_afscs = $this->afscs->fetchAll(AfscCollection::SHOW_ALL);

        $data = [
            'user' => $user,
            'afscs' => $tgt_afscs,
        ];

        return $this->render(
            'cards/category-new.html.twig',
            $data
        );
    }

    public function show_category_edit(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access_edit($user, $cat);

        $data = [
            'cat' => $cat,
        ];

        return $this->render(
            'cards/category-edit.html.twig',
            $data
        );
    }

    public function show_category_delete(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access_edit($user, $cat);

        $data = [
            'cat' => $cat,
        ];

        return $this->render(
            'cards/category-delete.html.twig',
            $data
        );
    }

    public function show_study(string $uuid): Response
    {
        $cat = $this->categories->fetch($uuid);

        if (!$cat || $cat->getUuid() === '') {
            $this->flash()->add(MessageTypes::WARNING,
                                'The specified flash card category could not be found');

            return $this->redirect('/cards');
        }

        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $this->check_access($user, $cat);

        $data = [
            'cat' => $cat,
        ];

        return $this->render(
            'cards/study.html.twig',
            $data
        );
    }

    public function show_home(): Response
    {
        $user = $this->users->fetch($this->auth_helpers->get_user_uuid());

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

        if (!$user) {
            $this->flash()->add(MessageTypes::WARNING,
                                'The system encountered an error while loading your user account');

            return $this->redirect("/auth/logout");
        }

        $n_cats = $this->categories->countUser($user);

        if (!$n_cats) {
            $this->flash()->add(MessageTypes::WARNING,
                                'There are no flash card categories in the database');

            return $this->redirect('/');
        }

        $cats = $this->categories->fetchAllByUser($user, $sort, $curPage * $numRecords, $numRecords);

        $pagination = ArrayPaginator::buildLinks(
            '/cards',
            $curPage,
            ArrayPaginator::calcNumPagesNoData(
                $n_cats,
                $numRecords
            ),
            $numRecords,
            $n_cats,
            $sortCol,
            $sortDir
        );

        $data = [
            'user' => $user,
            'cats' => $cats,
            'n_cats' => $n_cats,
            'pagination' => $pagination,
            'sort' => [
                'col' => $sortCol,
                'dir' => $sortDir,
            ],
        ];

        return $this->render(
            'cards/list.html.twig',
            $data
        );
    }
}