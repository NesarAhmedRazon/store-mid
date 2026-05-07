<?php

$snippets = $data['snippets'] ?? [];
$isEdit   = $data['isEdit']   ?? false;
$productId = $data['productId'] ?? null;
?>

<?php if (empty($data)): ?>
<div class="px-4 py-8 text-center font-mono text-[12px] text-subtle">
    No code snippets yet.
    <?php if ($isEdit): ?>
    <span class="text-muted"> Add one below.</span>
    <?php endif; ?>
</div>
<?php else: ?>

<!-- CodeMirror CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/jsx/jsx.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/shell/shell.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/rust/rust.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/lua/lua.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/php/php.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/display/autorefresh.min.js"></script>

<style>
.code-tabs { display:flex; border-bottom:1px solid var(--color-border); overflow-x:auto; }
.code-tab-btn {
    shrink: 0;
    padding: 10px 16px;
    font-family: var(--font-mono);
    font-size: 11px;
    border: none;
    border-bottom: 2px solid transparent;
    background: transparent;
    color: var(--color-subtle);
    cursor: pointer;
    white-space: nowrap;
    transition: color .15s, border-color .15s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.code-tab-btn:hover { color: var(--color-muted); }
.code-tab-btn.active { border-bottom-color: var(--color-text); color: var(--color-text); }
.lang-dot { width:7px; height:7px; border-radius:50%; display:inline-block; flex-shrink:0; }
.code-panel { display:none; }
.code-panel.active { display:block; }
.code-panel-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: #1e1e1e;
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.code-panel-meta { font-family: var(--font-mono); font-size: 11px; color: rgba(255,255,255,.45); display:flex; gap:16px; }
.copy-btn {
    font-family: var(--font-mono);
    font-size: 10px;
    color: rgba(255,255,255,.4);
    background: transparent;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 3px;
    padding: 2px 10px;
    cursor: pointer;
    transition: color .15s, border-color .15s;
}
.copy-btn:hover { color: rgba(255,255,255,.8); border-color: rgba(255,255,255,.3); }
.CodeMirror { height: auto; max-height: 480px; font-size: 12.5px; font-family: var(--font-mono); }
.CodeMirror-scroll { max-height: 480px; }
</style>

<?php
$langColors = [
    'arduino'     => '#00979C',
    'micropython' => '#3776AB',
    'python'      => '#3776AB',
    'c'           => '#A8B9CC',
    'cpp'         => '#00599C',
    'javascript'  => '#F7DF1E',
    'jsx'         => '#61DAFB',
    'typescript'  => '#3178C6',
    'bash'        => '#4EAA25',
    'rust'        => '#CE422B',
    'lua'         => '#000080',
    'php'         => '#8892BF',
    'html'        => '#E34F26',
    'css'         => '#1572B6',
    'makefile'    => '#427819',
    'text'        => '#999',
];

$cmModes = [
    'arduino'     => 'text/x-csrc',
    'micropython' => 'python',
    'python'      => 'python',
    'c'           => 'text/x-csrc',
    'cpp'         => 'text/x-c++src',
    'javascript'  => 'javascript',
    'jsx'         => 'javascript',
    'typescript'  => 'text/typescript',
    'bash'        => 'shell',
    'rust'        => 'rust',
    'lua'         => 'lua',
    'php'         => 'javascript',
    'html'        => 'htmlmixed',
    'css'         => 'css',
    'makefile'    => 'null',
    'text'        => 'null',
];

$cmThemes = [
    'vs-dark'   => 'dracula',
    'monokai'   => 'monokai',
    'dracula'   => 'dracula',
    'solarized' => 'default',
    'default'   => 'default',
];
?>

<!-- Tab nav -->
<div class="code-tabs">
    <?php foreach ($snippets as $i => $snippet): ?>
    <?php $color = $langColors[$snippet->language] ?? '#999'; ?>
    <button
        class="code-tab-btn <?= $i === 0 ? 'active' : '' ?>"
        onclick="switchCodeTab(<?= $i ?>)"
        id="code-tab-btn-<?= $i ?>"
    >
        <span class="lang-dot" style="background:<?= $color ?>"></span>
        <?= esc($snippet->file_name ?: ($snippet->title ?: strtoupper($snippet->language))) ?>
    </button>
    <?php endforeach; ?>
</div>

<!-- Panels -->
<?php foreach ($snippets as $i => $snippet):
    $theme  = $cmThemes[$snippet->editor_theme] ?? 'dracula';
    $mode   = $cmModes[$snippet->language]       ?? 'null';
    $color  = $langColors[$snippet->language]    ?? '#999';
?>
<div id="code-panel-<?= $i ?>" class="code-panel <?= $i === 0 ? 'active' : '' ?>">
    <div class="code-panel-head">
        <div class="code-panel-meta">
            <?php if ($snippet->title): ?>
            <span><?= esc($snippet->title) ?></span>
            <?php endif; ?>
            <?php if ($snippet->file_name): ?>
            <span style="color:rgba(255,255,255,.6)"><?= esc($snippet->file_name) ?></span>
            <?php endif; ?>
            <span style="color:<?= $color ?>"><?= esc(strtoupper($snippet->language)) ?></span>
        </div>
        <button class="copy-btn" onclick="copyCode(<?= $i ?>)">copy</button>
    </div>
    <textarea id="code-editor-<?= $i ?>" class="code-editor-ta" style="display:none"><?= htmlspecialchars($snippet->code) ?></textarea>
    <div id="code-cm-<?= $i ?>"></div>
</div>
<?php endforeach; ?>

<script>
(function () {
    var editors = [];
    var snippets = <?= json_encode(array_map(fn($s) => [
        'mode'  => $cmModes[$s->language] ?? 'null',
        'theme' => $cmThemes[$s->editor_theme] ?? 'dracula',
    ], $snippets)) ?>;

    function initEditor(i) {
        if (editors[i]) return;
        var ta = document.getElementById('code-editor-' + i);
        var wrap = document.getElementById('code-cm-' + i);
        var cfg = snippets[i];
        editors[i] = CodeMirror(wrap, {
            value:      ta.value,
            mode:       cfg.mode === 'null' ? null : cfg.mode,
            theme:      cfg.theme,
            readOnly:   true,
            lineNumbers: true,
            autoRefresh: true,
            viewportMargin: Infinity,
            extraKeys: {},
        });
    }

    window.switchCodeTab = function (i) {
        document.querySelectorAll('.code-panel').forEach(function (p) { p.classList.remove('active'); });
        document.querySelectorAll('.code-tab-btn').forEach(function (b) { b.classList.remove('active'); });
        document.getElementById('code-panel-' + i).classList.add('active');
        document.getElementById('code-tab-btn-' + i).classList.add('active');
        initEditor(i);
        if (editors[i]) editors[i].refresh();
    };

    window.copyCode = function (i) {
        var code = editors[i] ? editors[i].getValue() : document.getElementById('code-editor-' + i).value;
        navigator.clipboard.writeText(code).then(function () {
            var btn = document.querySelectorAll('.copy-btn')[i];
            btn.textContent = 'copied!';
            setTimeout(function () { btn.textContent = 'copy'; }, 1800);
        });
    };

    // Init first panel immediately
    document.addEventListener('DOMContentLoaded', function () { initEditor(0); });
}());
</script>

<?php endif; ?>
