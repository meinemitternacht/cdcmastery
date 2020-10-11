<?php
declare(strict_types=1);


namespace CDCMastery\Models\FlashCards\Sessions;


use CDCMastery\Helpers\ArrayHelpers;
use CDCMastery\Models\FlashCards\Category;
use Exception;
use Symfony\Component\HttpFoundation\Session\Session;

class CardSessionNative implements ICardSession
{
    private const SESSION_KEY = 'flash-card-sess';

    private Category $category;
    private int $cur_idx;
    private int $cur_state;
    private array $tgt_uuids;

    public static function save_session(Session $session, ICardSession $card_session): void
    {
        $session->set(self::SESSION_KEY, base64_encode(serialize($card_session)));
    }

    public static function resume_session(Session $session, Category $category): ?ICardSession
    {
        $card_session = $session->get(self::SESSION_KEY);

        if (!$card_session) {
            return null;
        }

        $card_session = base64_decode($card_session);

        if ($card_session === false) {
            return null;
        }

        $opts = [
            'allowed_classes' => [
                __CLASS__,
                Category::class,
            ],
        ];

        $card_session = unserialize($card_session, $opts);

        if (!$card_session instanceof self) {
            return null;
        }

        if ($card_session->getCategory()->getUuid() !== $category->getUuid()) {
            return null;
        }

        return $card_session;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): ICardSession
    {
        $this->category = $category;
        return $this;
    }

    public function getCurIdx(): int
    {
        return $this->cur_idx;
    }

    public function setCurIdx(int $cur_idx): ICardSession
    {
        $this->cur_idx = $cur_idx;
        return $this->setCurState(ICardSession::STATE_FRONT);
    }

    public function getCurState(): int
    {
        return $this->cur_state;
    }

    public function setCurState(int $cur_state): ICardSession
    {
        $this->cur_state = $cur_state;
        return $this;
    }

    public function countTgtUuids(): int
    {
        return count($this->tgt_uuids);
    }

    public function getTgtUuids(): array
    {
        return $this->tgt_uuids;
    }

    public function setTgtUuids(array $tgt_uuids): ICardSession
    {
        $this->tgt_uuids = $tgt_uuids;
        return $this;
    }

    /**
     * @return ICardSession
     * @throws Exception
     */
    public function shuffleTgtUuids(): ICardSession
    {
        ArrayHelpers::shuffle($this->tgt_uuids);
        $this->tgt_uuids = array_values($this->tgt_uuids);
        return $this;
    }
}
