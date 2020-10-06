<?php
declare(strict_types=1);
declare(ticks=1);


namespace CDCMastery\Models\CdcData\Archive;


use CDCMastery\Helpers\DateTimeHelpers;
use CDCMastery\Models\CdcData\Afsc;
use CDCMastery\Models\CdcData\QuestionAnswer;
use CDCMastery\Models\Tests\Test;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Tests\TestDataHelpers;
use DOMDocument;
use Monolog\Logger;
use RuntimeException;
use SimpleXMLElement;

class TestArchiver
{
    private Logger $log;
    private TestCollection $tests;
    private TestDataHelpers $test_data;
    /** @var resource $mutex */
    private $mutex;
    private int $total = 0;
    private int $done = 0;
    private int $pad_len = 0;
    private bool $shutdown = false;
    private bool $dry_run = false;

    private array $xml_queue = [];

    public function __construct(Logger $log, TestCollection $tests, TestDataHelpers $test_data)
    {
        if (!is_writable(XML_ARCHIVE_DIR)) {
            throw new RuntimeException('XML archive directory not writable');
        }

        $this->log = $log;
        $this->tests = $tests;
        $this->test_data = $test_data;
        $this->mutex = sem_get(ftok(__FILE__, 'a'));

        pcntl_signal(SIGINT, function (): void {
            $this->shutdown = true;
        });
    }

    private function lock(): void
    {
        sem_acquire($this->mutex);
    }

    private function unlock(): void
    {
        sem_release($this->mutex);
    }

    public function process(): void
    {
        try {
            $this->lock();

            $n_archivable = $this->tests->countArchivable();
            $n_archivable_fmt = number_format($n_archivable);

            if (!$n_archivable) {
                echo "There are no eligible tests to archive :)\n";
                exit;
            }

            echo "Archivable tests: {$n_archivable_fmt}\n";

            if ($this->dry_run) {
                echo "Dry run detected, aborting...\n";
                exit;
            }

            $this->pad_len = strlen($n_archivable_fmt) * 2 + 8;
            $pad = str_repeat(' ', $this->pad_len);
            echo "Progress: {$pad}";

            do {
                $this->total = $n_archivable;
                $tgt_tests = $this->tests->fetchArchivable(250);

                if (!$tgt_tests) {
                    $this->log->debug('test archiver: no target tests to archive');
                    break;
                }

                $this->archive_tests($tgt_tests);
            } while (true);

            echo "\n";
            echo "Finished archiving {$n_archivable_fmt} tests.\n";
            echo "Peak memory usage: " . (memory_get_peak_usage(true) / 1048576) . " MB\n";
        } finally {
            $this->unlock();
        }
    }

    /**
     * @param Test[] $tgt_tests
     */
    private function archive_tests(array $tgt_tests): void
    {
        if (!$tgt_tests) {
            return;
        }

        foreach ($tgt_tests as $tgt_test) {
            $qas = $this->test_data->list($tgt_test);

            if (!$qas) {
                $this->log->debug("test archiver: no test data for {$tgt_test->getUuid()}");
                $tgt_test->setQuestions([]);
                $tgt_test->setArchived(true);
                $this->done++;
                continue;
            }

            $this->archive_test($tgt_test, $qas);

            $this->done++;
            $done_fmt = number_format($this->done);
            $n_archivable_fmt = number_format($this->total);
            $pct = round(($this->done / $this->total) * 100);
            echo "\033[{$this->pad_len}D";
            echo str_pad("{$done_fmt}/{$n_archivable_fmt} ({$pct}%)", $this->pad_len);

            if ($this->shutdown) {
                break;
            }
        }

        $this->flush_xml_queue();
        $this->delete_test_data($tgt_tests);
        $this->tests->saveArray($tgt_tests);

        if ($this->shutdown) {
            throw new RuntimeException('user requested interrupt');
        }
    }

    /**
     * @param Test $test
     * @param QuestionAnswer[] $qas
     */
    private function archive_test(Test $test, array $qas): void
    {
        $this->create_directory($test->getUserUuid());
        $this->write_xml($test, $qas);
    }

    private function create_directory(string $user_uuid): void
    {
        if (is_dir(XML_ARCHIVE_DIR . "/{$user_uuid}")) {
            return;
        }

        if (!mkdir(XML_ARCHIVE_DIR . "/{$user_uuid}") &&
            !is_dir(XML_ARCHIVE_DIR . "/{$user_uuid}")) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', XML_ARCHIVE_DIR . "/{$user_uuid}"));
        }
    }

    /**
     * @param Test $test
     * @param QuestionAnswer[] $qas
     */
    private function write_xml(Test $test, array $qas): void
    {
        $time_started = $test->getTimeStarted();
        $time_completed = $test->getTimeCompleted();

        if (!$time_completed) {
            throw new RuntimeException("ERROR: test not complete :: test {$test->getUuid()}");
        }

        if ($test->isArchived()) {
            throw new RuntimeException("ERROR: test already archived :: test {$test->getUuid()}");
        }

        $time_completed_ts = $time_completed->getTimestamp();

        $afsc_names = array_map(static function (Afsc $v): string {
            return $v->getName();
        }, $test->getAfscs());

        $xml = new SimpleXMLElement('<xml/>');

        $td_elem = $xml->addChild('testDetails');
        $td_elem->addChild('timeStarted', $time_started
            ? $time_started->format(DateTimeHelpers::DT_FMT_DB)
            : 'UNKNOWN');
        $td_elem->addChild('timeCompleted', $time_completed->format(DateTimeHelpers::DT_FMT_DB));
        $td_elem->addChild('afscList', implode(',', $afsc_names));
        $td_elem->addChild('totalQuestions', (string)$test->getNumQuestions());
        $td_elem->addChild('questionsMissed', (string)$test->getNumMissed());
        $td_elem->addChild('testScore', (string)$test->getScore());

        foreach ($qas as $qa) {
            $answer = $qa->getAnswer();

            if (!$answer) {
                continue;
            }

            $q_elem = $xml->addChild('question');
            $q_elem->addChild('questionText',
                              htmlspecialchars($qa->getQuestion()->getText(), ENT_NOQUOTES | ENT_HTML5));
            $q_elem->addChild('answerGiven', htmlspecialchars($answer->getText(), ENT_NOQUOTES | ENT_HTML5));
            $q_elem->addChild('answerCorrect', $answer->isCorrect()
                ? 'yes'
                : 'no');
        }

        $dom = new DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        $data = $dom->saveXML();

        if ($data === false) {
            throw new RuntimeException("ERROR: DOM data was false :: test {$test->getUuid()}");
        }

        $path = XML_ARCHIVE_DIR . "/{$test->getUserUuid()}/{$time_completed_ts}#{$test->getUuid()}.xml";
        $this->xml_queue[ $path ] = $data;
        $test->setArchived(true);
    }

    private function delete_test_data(array $tests): void
    {
        if (!$this->test_data->delete_array($tests)) {
            throw new RuntimeException("ERROR: could not delete test data");
        }
    }

    private function flush_xml_queue(): void
    {
        if (!$this->xml_queue) {
            return;
        }

        foreach ($this->xml_queue as $path => $data) {
            if (!file_put_contents($path, $data)) {
                $size = strlen($data) / (1 << 20);
                throw new RuntimeException("ERROR: could not write XML to '{$path}' :: {$size} MB");
            }
        }

        $this->xml_queue = [];
    }

    /**
     * @param bool $dry_run
     */
    public function setDryRun(bool $dry_run): void
    {
        $this->dry_run = $dry_run;
    }
}