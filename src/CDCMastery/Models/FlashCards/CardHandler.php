<?php
declare(strict_types=1);


namespace CDCMastery\Models\FlashCards;


use CDCMastery\Models\Cache\CacheHandler;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\CdcDataCollection;
use Monolog\Logger;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\Session;
use Throwable;

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
    private Logger $log;
    private CacheHandler $cache;
    private CardCollection $cards;
    private CardSession $card_session;

    /**
     * CardHandler constructor.
     * @param Session $session
     * @param Logger $log
     * @param CacheHandler $cache
     * @param CardCollection $cards
     * @param CardSession $card_session
     */
    public function __construct(
        Session $session,
        Logger $log,
        CacheHandler $cache,
        CardCollection $cards,
        CardSession $card_session
    ) {
        $this->session = $session;
        $this->log = $log;
        $this->cache = $cache;
        $this->cards = $cards;
        $this->card_session = $card_session;
    }

    private static function cache_afsc_cards(CacheHandler $cache, CdcDataCollection $cdc_data, Afsc $afsc): array
    {
        $cache_params = [$afsc->getUuid()];
        $tgt_cards = CardHelpers::create_afsc_cards($cdc_data, $afsc);
        $uuids = [];
        foreach ($tgt_cards as $tgt_card) {
            $uuid = $tgt_card->getUuid();
            $key = self::CACHE_KEY_PREFIX_AFSC_CARDS . $uuid;
            $cache->hashAndSet($tgt_card,
                               $key,
                               CacheHandler::TTL_MAX);
            $uuids[] = $uuid;
        }

        $cache->hashAndSet($uuids, self::CACHE_KEY_PREFIX_AFSC_CARDS, CacheHandler::TTL_MAX, $cache_params);
        return $tgt_cards;
    }

    public static function factory(
        Session $session,
        Logger $log,
        CacheHandler $cache,
        AfscCollection $afscs,
        CdcDataCollection $cdc_data,
        CardCollection $cards,
        Category $category
    ): CardHandler {
        $card_session = CardSession::resume_session($session, $category);

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
                    if ($card_session) {
                        goto out_return;
                    }

                    $tgt_cards = array_flip($cached);
                    break;
                }

                $tgt_cards = self::cache_afsc_cards($cache, $cdc_data, $afsc);
                break;
            case Category::TYPE_PRIVATE:
            case Category::TYPE_GLOBAL:
            default:
                if ($card_session) {
                    goto out_return;
                }

                $tgt_cards = $cards->fetchCategory($category);
                break;
        }

        if (!$tgt_cards) {
            throw new RuntimeException('The selected category has no flash cards to view');
        }

        if ($card_session) {
            goto out_return;
        }

        $card_session = (new CardSession())->setCategory($category)
                                           ->setCurIdx(0)
                                           ->setCurState(CardSession::STATE_FRONT)
                                           ->setTgtUuids(array_keys($tgt_cards));

        out_return:
        return new self($session, $log, $cache, $cards, $card_session);
    }

    /**
     * @throws Throwable
     */
    public function first(): void
    {
        $this->card_session->setCurIdx(0);
        $this->save_session();
    }

    /**
     * @throws Throwable
     */
    public function previous(): void
    {
        if (($this->card_session->getCurIdx() - 1) <= 0) {
            $this->card_session->setCurIdx(0);
            goto out_return;
        }

        $this->card_session->setCurIdx($this->card_session->getCurIdx() - 1);

        out_return:
        $this->save_session();
    }

    /**
     * @throws Throwable
     */
    public function next(): void
    {
        $n_tgt_cards = $this->card_session->countTgtUuids();
        if (($this->card_session->getCurIdx() + 1) >= $n_tgt_cards) {
            $this->card_session->setCurIdx($n_tgt_cards - 1);
            goto out_return;
        }

        $this->card_session->setCurIdx($this->card_session->getCurIdx() + 1);

        out_return:
        $this->save_session();
    }

    /**
     * @throws Throwable
     */
    public function last(): void
    {
        $this->card_session->setCurIdx($this->card_session->countTgtUuids() - 1);
        $this->save_session();
    }

    /**
     * @throws Throwable
     */
    public function shuffle(): void
    {
        $this->card_session->shuffleTgtUuids();
        $this->first(); /* save handled by first() */
    }

    /**
     * @throws Throwable
     */
    public function flip(): void
    {
        $this->card_session->setCurState(
            $this->card_session->getCurState() === CardSession::STATE_FRONT
                ? CardSession::STATE_BACK
                : CardSession::STATE_FRONT
        );
        $this->save_session();
    }

    /**
     * @throws Throwable
     */
    private function save_session(): void
    {
        try {
            CardSession::save_session($this->session, $this->card_session);
        } catch (Throwable $e) {
            $this->log_debug(__METHOD__);
            throw $e;
        }
    }

    private function log_debug(string $method): void
    {
        $this->log->addDebug(<<<LOG
CARD SESSION DEBUG
------------------
method:     {$method}
session
  state:    {$this->card_session->getCurState()}
  idx:      {$this->card_session->getCurIdx()}
  total:    {$this->card_session->countTgtUuids()}
  category
    uuid:   {$this->card_session->getCategory()->getUuid()}
    name:   {$this->card_session->getCategory()->getName()}

(tgt uuids follow)
LOG
        );
        $tgt_uuids = $this->card_session->getTgtUuids();
        $this->log->addDebug($tgt_uuids
                                 ? implode(',', array_keys($tgt_uuids))
                                 : 'no tgt uuids');
    }

    public function display(): array
    {
        $tgt_card_uuids = $this->card_session->getTgtUuids();
        if (!isset($tgt_card_uuids[ $this->card_session->getCurIdx() ])) {
            $this->log_debug(__METHOD__);
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
            $this->log_debug(__METHOD__);
            throw new RuntimeException("Card does not exist ({$tgt_uuid})");
        }

        return [
            'category' => [
                'uuid' => $this->card_session->getCategory()->getUuid(),
            ],
            'card' => [
                'uuid' => $tgt_card->getUuid(),
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