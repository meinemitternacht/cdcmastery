<?php
declare(strict_types=1);


namespace CDCMastery\Models\Tests\Archive;


use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Users\User;
use DateTime;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SplFileInfo;
use Throwable;

class ArchiveReader
{
    private LoggerInterface $log;

    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
    }

    public function fetch_test(User $user, Test $test): ?ArchivedTest
    {
        if (!is_dir(XML_ARCHIVE_DIR)) {
            throw new RuntimeException('XML archive directory does not exist');
        }

        $user_uuid = $user->getUuid();
        $test_uuid = $test->getUuid();

        if (!is_dir(XML_ARCHIVE_DIR . "/{$user_uuid}")) {
            return null;
        }

        $matches = glob(XML_ARCHIVE_DIR . "/{$user_uuid}/*#{$test_uuid}.xml");

        if (!$matches) {
            return null;
        }

        $match = array_shift($matches);

        $finfo = new SplFileInfo($match);

        if (!$finfo->isReadable()) {
            $this->log->debug("xml archive file not readable :: {$match}");
            return null;
        }

        if (!$finfo->getSize()) {
            $this->log->debug("xml archive file empty :: {$match}");
            return null;
        }

        return $this->parse_xml($match);
    }

    private function parse_xml(string $path): ?ArchivedTest
    {
        $xml = new DOMDocument();
        if (!$xml->load($path)) {
            $this->log->debug("xml archive file load error :: {$path}");
            return null;
        }

        $details = $xml->getElementsByTagName('testDetails');
        $questions = $xml->getElementsByTagName('question');

        if (!$details->count()) {
            return null;
        }

        $detail = $details->item(0);

        if (!$detail) {
            return null;
        }

        $utc_tz = new DateTimeZone('UTC');
        $tgt_data = [];
        /** @var DOMElement $node */
        foreach ($detail->childNodes as $node) {
            $val = null;

            switch ($node->nodeName) {
                case 'timeStarted':
                case 'timeCompleted':
                    try {
                        $val = DateTime::createFromFormat('Y-m-d H:i:s', $node->nodeValue, $utc_tz);
                        if ($val === false) {
                            $val = null;
                        }
                    } catch (Throwable $e) {
                        $val = null;
                    }
                    break;
                case 'afscList':
                    $val = explode(',', $node->nodeValue);
                    break;
                case 'totalQuestions':
                case 'questionsMissed':
                    $val = (int)$node->nodeValue;
                    break;
                case 'testScore':
                    $val = (float)$node->nodeValue;
                    break;
                case 'testType':
                    switch ($node->nodeValue) {
                        case 'NORMAL':
                            $val = Test::TYPE_NORMAL;
                            break;
                        case 'PRACTICE':
                            $val = Test::TYPE_PRACTICE;
                            break;
                    }
                    break;
            }

            $tgt_data[ $node->nodeName ] = $val;
        }

        $tgt_data[ 'qaPairs' ] = [];

        if (!$questions->count()) {
            goto out_return;
        }

        /** @var DOMElement $question */
        foreach ($questions as $question) {
            $qdata = [];
            foreach ($question->childNodes as $node) {
                $val = null;

                switch ($node->nodeName) {
                    case 'questionText':
                    case 'answerGiven':
                        $val = htmlspecialchars_decode($node->nodeValue);
                        break;
                    case 'answerCorrect':
                        $val = mb_strtolower($node->nodeValue) === 'yes';
                        break;
                }

                $qdata[ $node->nodeName ] = $val;
            }

            try {
                $tgt_data[ 'qaPairs' ][] = new ArchivedTestQAPair($qdata[ 'questionText' ],
                                                                  $qdata[ 'answerGiven' ],
                                                                  $qdata[ 'answerCorrect' ]);
            } catch (Throwable $e) {
                $this->log->debug("xml archive file parser :: {$e}");
                unset($e);
            }
        }

        out_return:
        try {
            return new ArchivedTest($tgt_data[ 'timeStarted' ],
                                    $tgt_data[ 'timeCompleted' ],
                                    $tgt_data[ 'afscList' ],
                                    $tgt_data[ 'totalQuestions' ],
                                    $tgt_data[ 'questionsMissed' ],
                                    $tgt_data[ 'testScore' ],
                                    $tgt_data[ 'qaPairs' ],
                                    $tgt_data[ 'testType' ] ?? Test::TYPE_NORMAL);
        } catch (Throwable $e) {
            $this->log->debug("xml archive file parser :: {$e}");
            return null;
        }
    }
}
