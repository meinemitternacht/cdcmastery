<?php
declare(strict_types=1);


namespace CDCMastery\Models\FlashCards;


use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\CdcDataCollection;
use Monolog\Logger;
use mysqli;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\Session;

class CardHandler
{
    private const CACHE_KEY_PREFIX_AFSC_CARDS = 'fc-';

    public const ACTION_NO_ACTION = -1;
    public const ACTION_SHUFFLE = 0;
    public const ACTION_NAV_FIRST = 1;
    public const ACTION_NAV_PREV = 2;
    public const ACTION_NAV_NEXT = 3;
    public const ACTION_NAV_LAST = 4;
    public const ACTION_FLIP_CARD = 5;

    private Session $session;
    private mysqli $db;
    private Logger $log;
    private CacheHandler $cache;
    private CardCollection $cards;
    private CardSession $card_session;

    /**
     * CardHandler constructor.
     * @param Session $session
     * @param mysqli $db
     * @param Logger $log
     * @param CardSession $card_session
     */
    public function __construct(
        Session $session,
        mysqli $db,
        Logger $log,
        CacheHandler $cache,
        CardCollection $cards,
        CardSession $card_session
    ) {
        $this->session = $session;
        $this->db = $db;
        $this->log = $log;
        $this->cache = $cache;
        $this->cards = $cards;
        $this->card_session = $card_session;
    }

    public static function factory(
        Session $session,
        mysqli $db,
        Logger $log,
        CacheHandler $cache,
        AfscCollection $afscs,
        CdcDataCollection $cdc_data,
        CardCollection $cards,
        Category $category
    ): CardHandler {
        $card_session = CardSession::resume_session($session, $category);

        if ($card_session) {
            goto out_return;
        }

        switch ($category->getType()) {
            case Category::TYPE_AFSC:
                $binding = $category->getBinding();
                if (!$binding) {
                    throw new RuntimeException('Cannot load AFSC data without binding');
                }

                $afsc = $afscs->fetch($binding);

                if (!$afsc) {
                    throw new RuntimeException('AFSC not found');
                }

                $cache_params = [$afsc->getUuid()];
                $cached = $cache->hashAndGet(self::CACHE_KEY_PREFIX_AFSC_CARDS, $cache_params);

                if (is_array($cached)) {
                    $tgt_cards = $cached;
                    break;
                }

                $tgt_cards = CardHelpers::create_afsc_cards($cdc_data, $afsc);
                foreach ($tgt_cards as $tgt_card) {
                    $key = self::CACHE_KEY_PREFIX_AFSC_CARDS . $tgt_card->getUuid();
                    $cache->hashAndSet($tgt_card,
                                       $key,
                                       CacheHandler::TTL_MAX);
                }
                break;
            case Category::TYPE_PRIVATE:
            case Category::TYPE_GLOBAL:
            default:
                $tgt_cards = $cards->fetchCategory($category);
                break;
        }

        $card_session = (new CardSession())->setCategory($category)
                                           ->setCurIdx(0)
                                           ->setCurState(CardSession::STATE_FRONT)
                                           ->setTgtUuids(array_keys($tgt_cards));

        out_return:
        return new self($session, $db, $log, $cache, $cards, $card_session);
    }

    public function first(): void
    {
        $this->card_session->setCurIdx(0);
        CardSession::save_session($this->session, $this->card_session);
    }

    public function previous(): void
    {
        if (($this->card_session->getCurIdx() - 1) <= 0) {
            $this->card_session->setCurIdx(0);
            goto out_return;
        }

        $this->card_session->setCurIdx($this->card_session->getCurIdx() - 1);

        out_return:
        CardSession::save_session($this->session, $this->card_session);
    }

    public function next(): void
    {
        $n_tgt_cards = $this->card_session->countTgtUuids();
        if (($this->card_session->getCurIdx() + 1) >= $n_tgt_cards) {
            $this->card_session->setCurIdx($n_tgt_cards - 1);
            goto out_return;
        }

        $this->card_session->setCurIdx($this->card_session->getCurIdx() + 1);

        out_return:
        CardSession::save_session($this->session, $this->card_session);
    }

    public function last(): void
    {
        $this->card_session->setCurIdx($this->card_session->countTgtUuids() - 1);
        CardSession::save_session($this->session, $this->card_session);
    }

    public function shuffle(): void
    {
        $this->card_session->shuffleTgtUuids();
        $this->first(); /* save handled by first() */
    }

    public function flip(): void
    {
        $this->card_session->setCurState(
            $this->card_session->getCurState() === CardSession::STATE_FRONT
                ? CardSession::STATE_BACK
                : CardSession::STATE_FRONT
        );
        CardSession::save_session($this->session, $this->card_session);
    }

    public function display(): array
    {
        $tgt_card_uuids = $this->card_session->getTgtUuids();
        if (!isset($tgt_card_uuids[ $this->card_session->getCurIdx() ])) {
            throw new RuntimeException('Card index out of bounds');
        }

        $tgt_uuid = $tgt_card_uuids[ $this->card_session->getCurIdx() ];

        $tgt_card = null;
        switch ($this->card_session->getCategory()->getType()) {
            case Category::TYPE_AFSC:
                $key = self::CACHE_KEY_PREFIX_AFSC_CARDS . $tgt_uuid;
                $tgt_card = $this->cache->hashAndGet($key)
                    ?: null;
                break;
            case Category::TYPE_GLOBAL:
            case Category::TYPE_PRIVATE:
            default:
                $tgt_card = $this->cards->fetch($this->card_session->getCategory(),
                                                $tgt_uuid);
                break;
        }

        if (!$tgt_card) {
            throw new RuntimeException("Card does not exist ({$tgt_uuid})");
        }

        return [
            'category' => [
                'uuid' => $this->card_session->getCategory()->getUuid(),
            ],
            'cards' => [
                'idx' => $this->card_session->getCurIdx(),
                'total' => $this->card_session->countTgtUuids(),
            ],
            'display' => [
                'front' => "<strong>Question:</strong><br>" . nl2br($tgt_card->getFront()),
                'back' => "<strong>Answer:</strong><br>" . nl2br($tgt_card->getBack()),
                'state' => CardSession::STATE_STRINGS[ $this->card_session->getCurState() ] ?? null,
            ],
        ];
    }
}