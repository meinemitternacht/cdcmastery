<?php
declare(strict_types=1);

namespace CDCMastery\Models\FlashCards\Sessions;

use CDCMastery\Models\FlashCards\Category;
use Exception;

interface ICardSession
{
    public const STATE_FRONT = 0;
    public const STATE_BACK = 1;

    public const STATE_STRINGS = [
        self::STATE_FRONT => 'front',
        self::STATE_BACK => 'back',
    ];

    public function getCategory(): Category;

    public function setCategory(Category $category): ICardSession;

    public function getCurIdx(): int;

    public function setCurIdx(int $cur_idx): ICardSession;

    public function getCurState(): int;

    public function setCurState(int $cur_state): ICardSession;

    public function countTgtUuids(): int;

    public function getTgtUuids(): array;

    public function setTgtUuids(array $tgt_uuids): ICardSession;

    /**
     * @return $this
     * @throws Exception
     */
    public function shuffleTgtUuids(): ICardSession;
}