<?php
// Simple quiz viewer + grader — reads questions from Quiz.txt
// Usage: place both `quiz.php` and `Quiz.txt` in the same folder (already present).

$QUIZ_FILE = __DIR__ . '/Quiz.txt';

function parse_quiz_file($path) {
    if (!is_readable($path)) return [];
    $text = file_get_contents($path);
    // normalize newlines and remove BOM
    $text = preg_replace('/\r\n?/', "\n", $text);
    $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);

    // Split into blocks separated by one or more blank lines
    $blocks = preg_split('/\n\s*\n+/', trim($text));
    $questions = [];

    foreach ($blocks as $block) {
        $lines = array_values(array_filter(array_map('trim', explode("\n", $block)), 'strlen'));
        if (count($lines) < 2) continue; // not a valid question block

        // Last line normally contains ANSWER: ...
        $last = array_pop($lines);

        $answerLine = null;
        if (preg_match('/^ANSWER\s*:\s*(.*)$/i', $last, $m)) {
            $answerLine = trim($m[1]);
        } else {
            // maybe ANSWER present earlier; search backward
            foreach (array_reverse($lines, true) as $k => $l) {
                if (preg_match('/^ANSWER\s*:\s*(.*)$/i', $l, $m2)) {
                    $answerLine = trim($m2[1]);
                    unset($lines[$k]);
                    break;
                }
            }
        }

        if ($answerLine === null) continue; // invalid block — no answer found

        // parse answers: comma separated letters A..D (case-insensitive)
        $rawAnswers = preg_split('/[,;]+/', $answerLine);
        $answers = array_map(function($a){return strtoupper(trim($a));}, array_filter($rawAnswers, 'strlen'));

        // first line is the question text
        $questionText = array_shift($lines);

        // the rest are options like "A. text"
        $options = [];
        foreach ($lines as $ln) {
            if (preg_match('/^([A-Z])\s*\.?\s*(.*)$/i', $ln, $mm)) {
                $label = strtoupper($mm[1]);
                $textOpt = trim($mm[2]);
                $options[$label] = $textOpt;
            } else {
                // non-standard, append as additional text
                $options[] = $ln;
            }
        }

        if (count($options) === 0) continue;

        $questions[] = [
            'question' => $questionText,
            'options' => $options,
            'answers' => array_values($answers),
        ];
    }

    return $questions;
}

$questions = parse_quiz_file($QUIZ_FILE);
$total = count($questions);

$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$results = [];
$score = 0;

if ($submitted) {
    foreach ($questions as $i => $q) {
        $qid = 'q' . $i;
        $correctSet = array_map('strtoupper', $q['answers']);
        sort($correctSet);

        $userRaw = isset($_POST[$qid]) ? $_POST[$qid] : [];
        // normalize to array
        if (!is_array($userRaw)) $userRaw = [$userRaw];
        $user = array_map('strtoupper', array_map('trim', $userRaw));
        $user = array_values(array_filter($user, 'strlen'));
        $userSet = $user;
        sort($userSet);

        // exact match (order doesn't matter)
        $isCorrect = ($userSet === $correctSet);
        if ($isCorrect) $score++;

        $results[$i] = [
            'correct' => $isCorrect,
            'user' => $user,
            'correctAnswers' => $correctSet,
        ];
    }
}

function esc($s){ return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Quiz — Bài trắc nghiệm</title>
    <style>
        body{font-family:system-ui,Segoe UI,Roboto,Arial;margin:24px;background:#f6f7fb;color:#111}
        .container{max-width:900px;margin:0 auto;background:#fff;padding:22px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.08)}
        h1{margin-top:0}
        .question{margin:18px 0;padding:12px;border-radius:6px;border:1px solid #eee}
        .qtext{font-weight:600;margin-bottom:8px}
        .options{display:flex;flex-direction:column;gap:6px}
        label.option{display:flex;align-items:center;padding:8px;border-radius:6px;border:1px solid transparent}
        label.option input{margin-right:10px}
        .correct{background:#e6ffed;border-color:#b7f0c6}
        .wrong{background:#fff0f0;border-color:#f2bdbd}
        .neutral{background:transparent}
        .meta{font-size:14px;color:#555;margin-top:8px}
        .score{padding:12px;border-radius:6px;background:#f2f7ff;border:1px solid #d6e5ff;margin-bottom:12px}
        .btn{padding:10px 14px;border-radius:6px;border:0;background:#2b6bf4;color:white;cursor:pointer}
        .small{font-size:13px;color:#666}
        .correctAnswerHint{font-weight:700;color:#117a3a}
        .wrongSelected{color:#a80000;font-weight:700}
    </style>
</head>
<body>
<div class="container">
    <h1>Bài trắc nghiệm</h1>
    <p class="small">Nội dung được đọc từ file <code>Quiz.txt</code>. Chọn đáp án rồi gửi để xem câu nào đúng / sai.</p>

    <?php if ($submitted): ?>
        <div class="score">Bạn đạt <strong><?= $score ?>/<?= $total ?></strong> điểm (<?= $total ? round($score/$total*100) : 0 ?>%).</div>
    <?php endif; ?>

    <form method="post" novalidate>
        <?php foreach ($questions as $idx => $q):
            $qIdx = 'q' . $idx;
            $isMulti = count($q['answers']) > 1;
            $userAnswers = [];
            if ($submitted && isset($results[$idx])) $userAnswers = $results[$idx]['user'];
        ?>
            <div class="question">
                <div class="qtext"><?= ($idx+1) . '. ' . esc($q['question']) ?></div>
                <div class="options">
                <?php foreach ($q['options'] as $letter => $opt):
                    $label = esc($letter);
                    $text = esc($opt);
                    $checked = '';
                    if ($submitted) {
                        // decide classes for color
                        $isCorrectOpt = in_array($letter, $q['answers'], true);
                        $isUserSelected = in_array($letter, $userAnswers, true);
                        $class = 'neutral';
                        if ($isCorrectOpt) $class = 'correct';
                        if ($isUserSelected && !$isCorrectOpt) $class = 'wrong';
                        $checked = $isUserSelected ? 'checked' : '';
                    } else {
                        $class = 'neutral';
                        // keep input persistent if form posted
                        if (isset($_POST[$qIdx])) {
                            if (is_array($_POST[$qIdx]) && in_array($letter, $_POST[$qIdx])) $checked = 'checked';
                            if (!is_array($_POST[$qIdx]) && $_POST[$qIdx] === $letter) $checked = 'checked';
                        }
                    }
                    ?>
                    <label class="option <?= $class ?>">
                        <?php if ($isMulti): ?>
                            <input type="checkbox" name="<?= $qIdx ?>[]" value="<?= $label ?>" <?= $checked ?> />
                        <?php else: ?>
                            <input type="radio" name="<?= $qIdx ?>" value="<?= $label ?>" <?= $checked ?> />
                        <?php endif; ?>
                        <span style="flex:1"><strong><?= $label ?>.</strong> <?= $text ?></span>
                    </label>
                <?php endforeach; ?>
                </div>

                <?php if ($submitted):
                    $res = $results[$idx];
                    if ($res['correct']): ?>
                        <div class="meta correctAnswerHint">Đáp án: <?= implode(', ', $res['correctAnswers']) ?> — <strong>Đúng</strong></div>
                    <?php else: ?>
                        <div class="meta wrongSelected">Đáp án đúng: <?= implode(', ', $res['correctAnswers']) ?> — <strong>Sai</strong>
                            <?php if (!empty($res['user'])): ?> — Bạn chọn: <?= implode(', ', $res['user']) ?><?php endif; ?></div>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        <?php endforeach; ?>

        <div style="margin-top:14px">
            <button class="btn" type="submit">Nộp bài</button>
            <?php if ($submitted): ?>
                <a href="<?= esc(basename(__FILE__)) ?>" style="margin-left:12px;display:inline-block;padding:10px 12px;background:#e9eefc;border-radius:6px;color:#1a4ab5;text-decoration:none">Làm lại</a>
            <?php endif; ?>
        </div>
    </form>

    <hr style="margin-top:22px">
    <div class="small">Nhận xét: trang này tự động parse file <code>Quiz.txt</code>. Nếu câu có nhiều đáp án (ví dụ "ANSWER: C, D") hệ thống sẽ hiển thị checkbox và yêu cầu chọn chính xác các đáp án để tính là đúng.</div>
</div>
</body>
</html>
