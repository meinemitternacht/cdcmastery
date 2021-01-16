<?php
declare(strict_types=1);

use CDCMastery\Helpers\UUID;
use CDCMastery\Models\CdcData\AfscCollection;
use CDCMastery\Models\CdcData\CdcDataCollection;
use CDCMastery\Models\CdcData\Question;
use CDCMastery\Models\CdcData\QuestionAnswers;
use CDCMastery\Models\CdcData\QuestionCollection;
use CDCMastery\Models\Config\Config;
use CDCMastery\Models\FlashCards\Card;
use CDCMastery\Models\FlashCards\CardCollection;
use CDCMastery\Models\FlashCards\Category;
use CDCMastery\Models\FlashCards\CategoryCollection;
use CDCMastery\Models\Tests\TestCollection;
use CDCMastery\Models\Users\UserCollection;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/** @var ContainerInterface $c */
$c = require realpath(__DIR__) . "/../src/CDCMastery/Bootstrap.php";

$good_questions = [
    '00c837dd-770a-406b-a3fa-0469e740967e',
    '020b2755-dc4d-4976-b018-d460056c59d4',
    '0a39c255-fb9f-49fc-b47e-4840dc84efc5',
    '0c1c7c04-6c83-4982-aef3-c9817f5e27af',
    '108cdf21-6b2d-423f-908b-d81051d2701f',
    '10ec88c6-6ee4-4a04-b5f2-7ff293d5e682',
    '1286e51c-f792-4a7c-80d2-27df5289753f',
    '129016be-0fc9-4985-a444-afbf5cee35bd',
    '15fc8d8a-5618-4a46-b68b-cae7c9f4798b',
    '177479ad-3fd9-4e5e-82bb-102a470899f9',
    '18638558-a4ea-43a8-b288-7845b4041fed',
    '1bad40ef-19c6-4a1c-b90e-0772437ffa14',
    '1c3a6cc6-e5d1-4ce3-bc1f-a51d7e2e18bd',
    '1cb36690-da1f-4bf9-a242-3d5999830622',
    '1cf6ff9e-adf2-4077-9ba2-4a6f5c080463',
    '1f1773b4-20a5-489e-b107-ee9d023d7b16',
    '21a91193-2702-4329-881a-dd4f35e61c49',
    '28341468-886a-4941-b5f5-a76f67d5d42d',
    '309ad91f-ae81-4b52-b426-8aa4c7fd5cda',
    '30d401cc-dd06-4ba8-8f6a-a65e889e2e12',
    '31104506-f937-43a5-a5ef-5f5d8d21b7c3',
    '359cb897-f62c-407b-a043-38b23225f66c',
    '36809a32-4f6a-4111-a459-a00e45518839',
    '38c7e54d-e083-4449-9765-a9a0aed8e4da',
    '3998dc67-c830-45a3-9f15-58c9eed2f8f8',
    '3ba3cc5a-23ee-4dd4-8e4b-774432b4f223',
    '3c7c2b11-9bc3-4857-b311-e4f7781581e6',
    '3d7a10ef-9764-4fa2-8a9f-85cf499e0fdc',
    '3d894692-af82-4c6b-abc7-27f1a07137ca',
    '411ef99f-c3a7-4242-bca5-046fbdd2384e',
    '426d4549-acc6-4149-80e5-8024485d5091',
    '4488675f-be32-45d1-b5ac-9f8ad3a1a4e1',
    '48c8d88a-6014-4f19-aa76-f64fb30d6cad',
    '4abf8802-198c-4a2b-9a71-166622068c38',
    '4c14d575-1151-4479-9eba-bd67d04074f1',
    '4caa288b-6a38-4561-b205-5ddb7cc7fb01',
    '509834fe-4e82-4b3e-928e-e6e4abae5eeb',
    '5451c277-e49d-4c35-ac69-1dfad095c756',
    '547399ee-df1d-44d1-b48f-cf30ee655ab8',
    '575d1b94-41ea-4a88-9e9f-fafd1018375d',
    '5b180051-686b-44b1-8087-7abf975bf881',
    '5c8b17c4-90c6-46cb-a5ed-85a9d98e9204',
    '5e118a6c-23f9-4dc0-997a-032a6c343388',
    '62d9d127-2538-4e64-b769-3cf0f5b7b41e',
    '63b0340f-d6c8-4c5c-97e1-d96b85fb26c7',
    '6639254c-3a94-4df9-b80d-17b06f8579f2',
    '666ad5eb-e145-4d45-be0a-ba67e284dc99',
    '6a36ba76-7ae6-4986-8fbf-af1388ea0c65',
    '6b2e7cf0-025b-4367-b9ae-474023b2aa1f',
    '6c0b9f77-5d1c-4ff5-993b-a4be5d82f2f0',
    '7599d7e2-12fb-4e0c-98d2-5065219b1bb2',
    '7c8eb930-f794-46ec-97ef-3dd4ce2ea17a',
    '7da810e6-8653-47fb-bd4a-f17ea07af5d0',
    '7ec14d06-817d-4255-bd92-bee98e226097',
    '7f79697e-7c8f-4dac-9355-e87719ce891b',
    '7ffdd819-a5c8-4f1b-897c-05d4c89d6844',
    '8401e418-6af4-44d3-a186-7d48feaf8382',
    '88ca19ff-63f3-420e-a836-da75853b14c0',
    '8a4678c0-e2f5-4b9d-9809-09d10f2ecc20',
    '9282cb24-5ce5-4267-a7ea-f441e600d2d2',
    '92e57743-047e-4440-9a9f-166fb467b425',
    '933bffe4-95f7-483c-9c20-2b5304ee12d3',
    '969e8db3-bb04-422e-bee7-91b1149ec930',
    '97891dbe-8351-4960-b7f9-632922a595fa',
    '989d48cc-65bd-4080-8ba7-8030e1efc081',
    '9b8b3fac-0846-4a5e-a32f-11520e3698a3',
    '9c960f18-b0bb-4374-a2c1-60221ade2048',
    'a20c13bb-a866-413f-acf6-f8a7782955d7',
    'a8727abf-a9fc-408c-a486-8490a13d2587',
    'aef2a061-14dd-4a5a-8fa6-1fee03742c58',
    'af412feb-aff7-49ed-be74-526dea7eb04a',
    'b3fe27e2-c0e7-4016-980d-612f732bf497',
    'b57a79a3-9a05-43ce-bdfb-0a0ddd5eb833',
    'b8819fcf-c807-47a2-8446-664167ed1d78',
    'b8db2b15-c763-4789-87a6-619b1ca3ce31',
    'b98241c3-ff75-4892-ad03-266eda6ea46e',
    'bac178f8-45b7-47d6-aba7-a9ee3afced5c',
    'bbab1ef0-5cbd-43ea-9e37-8c0ed4090722',
    'bc923e96-e69b-4c03-a736-f0922fe177b8',
    'be918611-d983-4aa3-8b05-3e9585e8379f',
    'bea0d7ac-ab56-4086-a078-abc884c67850',
    'c48fef39-137c-49c3-b5de-cd63de0067ff',
    'c83721a4-c55e-4def-9151-636c56d9aa3e',
    'cb733ffe-7b6a-4cd8-b745-941c9e0e3807',
    'cbef5a11-64ae-4d68-8d51-44da6805b6f8',
    'cf20237a-8615-4c7f-8804-dae5cbb81894',
    'd44ecc0a-033d-43a8-a443-9d4155b26196',
    'd9e05d58-0907-442e-99fc-0031f0d4bbd5',
    'dc190da1-f0cd-4c91-b576-ba41a8b31ddc',
    'df651b2a-ddaa-48bb-8aed-15ba684675aa',
    'e99ab7e2-e1ed-420f-8417-a6fcf9c09db6',
    'e9c71f54-ad44-4a2b-bdda-cd51174452ea',
    'ec4e868d-7358-4054-9878-635701726e1f',
    'ece8a2d3-df9f-4324-a579-f92f1195e00d',
    'eebd41bf-eb13-485d-8d7f-870e5720c668',
    'f1580c61-1d8f-418e-b66f-7be25cb6a817',
    'fa1a901e-b226-4bae-8c21-74dabaf4fa98',
    'fdccc8b5-abe7-44b7-b147-9796a3a1e386',
];

$log = $c->get(LoggerInterface::class);
$config = $c->get(Config::class);
$afscs = $c->get(AfscCollection::class);
$cdc_data = $c->get(CdcDataCollection::class);
$fc_cats = $c->get(CategoryCollection::class);
$fc_cards = $c->get(CardCollection::class);
$users = $c->get(UserCollection::class);
$questions = $c->get(QuestionCollection::class);
$tests = $c->get(TestCollection::class);

$sys_user = $users->fetch($config->get(['system', 'user']));

$afsc = $afscs->fetch('2af71c41-05d9-4a38-9c98-fb7914fac28c');
$cdc = $cdc_data->fetch($afsc);

load_data: {
    $qa_keyed = [];
    foreach ($cdc->getQuestionAnswerData() as $qa) {
        $qa_keyed[ $qa->getQuestion()->getUuid() ] = $qa;
    }

    /** @var QuestionAnswers[] $qa_diff */
    $qa_diff = array_diff_key(array_flip($qa_keyed), $good_questions);

    if (!$qa_diff) {
        echo "no foreign questions found\n";
        exit;
    }

    $n_qa_diff = count($qa_diff);
    $bad_questions = array_map(static function (QuestionAnswers $v): Question {
        return $v->getQuestion();
    }, $qa_diff);
    echo "found {$n_qa_diff} foreign questions\n";
}

create_cards: {
    $cat_uuid = UUID::generate();
    $cat = new Category();
    $cat->setUuid($cat_uuid);
    $cat->setBinding($afsc->getUuid());
    $cat->setType(Category::TYPE_GLOBAL);
    $cat->setName('2W171 Self Test Questions');
    $cat->setCreatedBy($sys_user->getUuid());
    $cat->setEncrypted($afsc->isFouo());

    $fc_cats->save($cat);
    echo "created category: {$cat_uuid}\n";

    $new_cards = [];
    foreach ($qa_diff as $qa) {
        $card = new Card();
        $card->setUuid(UUID::generate());
        $card->setCategory($cat_uuid);
        $card->setFront($qa->getQuestion()->getText());
        $card->setBack($qa->getCorrect()->getText());
        $new_cards[] = $card;

        echo "new card: {$card->getUuid()}\n";
    }

    $fc_cards->saveArray($cat, $new_cards);
}

disable_questions: {
    foreach ($bad_questions as $bad_question) {
        $bad_question->setDisabled(true);
    }

    echo "disabling foreign questions...";
    $questions->saveArray($afsc, $bad_questions);
    echo "done\n";
}

delete_tests: {
    echo "finding associated tests...";
    $tgt_tests = $tests->fetchAllByQuestions($bad_questions);

    if (!$tgt_tests) {
        echo "no associated tests found\n";
        exit;
    }

    $n_tgt_tests = count($tgt_tests);
    echo "found {$n_tgt_tests} tests\n";
    $tests->deleteArray($tgt_tests);

    echo "deleted tests\n";
}
