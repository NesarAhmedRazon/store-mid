<?php
use App\Models\CodeModel;

$snippets  = $data['snippets']  ?? [];
$productId = $data['productId'] ?? null;

$languages = CodeModel::LANGUAGES;
$themes    = CodeModel::THEMES;

$cmModes = [
    'arduino'     => 'text/x-csrc',
    'micropython' => 'python',
    'python'      => 'python',
    'c'           => 'text/x-csrc',
    'cpp'         => 'text/x-c++src',
    'javascript'  => 'javascript',
    'typescript'  => 'text/typescript',
    'bash'        => 'shell',
    'rust'        => 'rust',
    'lua'         => 'lua',
    'makefile'    => 'null',
    'text'        => 'null',
];
?>

<!-- CodeMirror CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/monokai.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/clike/clike.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/python/python.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/shell/shell.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/rust/rust.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/lua/lua.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/display/autorefresh.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/matchbrackets.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/edit/closebrackets.min.js"></script>

<style>
.snippet-card {
    border: 1px solid var(--color-border);
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 12px;
}
.snippet-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    background: var(--color-bg);
    border-bottom: 1px solid var(--color-border);
    cursor: pointer;
    user-select: none;
}
.snippet-head-label {
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--color-text);
    flex: 1;
}
.snippet-head-lang {
    font-family: var(--font-mono);
    font-size: 10px;
    color: var(--color-subtle);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    padding: 2px 8px;
    border-radius: 3px;
}
.snippet-remove-btn {
    font-family: var(--font-mono);
    font-size: 10px;
    color: var(--color-down);
    background: transparent;
    border: 1px solid rgba(155,32,32,.3);
    border-radius: 3px;
    padding: 2px 8px;
    cursor: pointer;
    transition: border-color .15s;
}
.snippet-remove-btn:hover { border-color: var(--color-down); }
.snippet-body { padding: 14px; display: flex; flex-col: column; gap: 12px; }
.snippet-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.snippet-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; }
.snippet-field label { display:block; font-size:10px; text-transform:uppercase; letter-spacing:.08em; color:var(--color-subtle); margin-bottom:5px; }
.snippet-field input,
.snippet-field select {
    width: 100%;
    font-family: var(--font-mono);
    font-size: 12px;
    color: var(--color-text);
    background: var(--color-bg);
    border: 1px solid var(--color-border);
    border-radius: 6px;
    padding: 7px 10px;
    outline: none;
    transition: border-color .15s;
}
.snippet-field input:focus,
.snippet-field select:focus { border-color: var(--color-border-md); }
.cm-wrap { border: 1px solid var(--color-border); border-radius: 6px; overflow: hidden; margin-top: 12px; }
.cm-wrap .CodeMirror { height: 320px; font-size: 12.5px; font-family: var(--font-mono); }
</style>

<div id="snippets-container" class="p-4 flex flex-col gap-3">
    <?php foreach ($snippets as $i => $s): ?>
    <?php $lang = $s->language ?? 'text'; ?>
    <div class="snippet-card" id="snippet-<?= $i ?>">
        <div class="snippet-head" onclick="toggleSnippet(<?= $i ?>)">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" id="chevron-<?= $i ?>" style="transition:transform .15s">
                <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            </svg>
            <span class="snippet-head-label"><?= esc($s->file_name ?: ($s->title ?: 'Snippet ' . ($i + 1))) ?></span>
            <span class="snippet-head-lang"><?= esc(strtoupper($lang)) ?></span>
            <button type="button" class="snippet-remove-btn" onclick="event.stopPropagation();removeSnippet(<?= $i ?>)">remove</button>
        </div>
        <div class="snippet-body" id="snippet-body-<?= $i ?>">
            <input type="hidden" name="code[<?= $i ?>][id]" value="<?= $s->id ?? '' ?>">

            <div class="snippet-row-3">
                <div class="snippet-field">
                    <label>Title</label>
                    <input type="text" name="code[<?= $i ?>][title]" value="<?= esc($s->title ?? '') ?>" placeholder="e.g. Blink LED">
                </div>
                <div class="snippet-field">
                    <label>File name</label>
                    <input type="text" name="code[<?= $i ?>][file_name]" value="<?= esc($s->file_name ?? '') ?>" placeholder="e.g. blink.ino">
                </div>
                <div class="snippet-field">
                    <label>Language</label>
                    <select name="code[<?= $i ?>][language]" id="lang-select-<?= $i ?>" onchange="changeLanguage(<?= $i ?>)">
                        <?php foreach ($languages as $key => $cfg): ?>
                        <option value="<?= $key ?>" <?= $lang === $key ? 'selected' : '' ?>><?= $cfg['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="snippet-field" style="margin-top:4px">
                <label>Theme</label>
                <select name="code[<?= $i ?>][editor_theme]" id="theme-select-<?= $i ?>" onchange="changeTheme(<?= $i ?>)" style="width:180px">
                    <?php foreach ($themes as $key => $label): ?>
                    <option value="<?= $key ?>" <?= ($s->editor_theme ?? 'vs-dark') === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="cm-wrap">
                <textarea id="cm-ta-<?= $i ?>" style="display:none" name="code[<?= $i ?>][code]"><?= htmlspecialchars($s->code ?? '') ?></textarea>
                <div id="cm-host-<?= $i ?>"></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="px-4 pb-4">
    <button
        type="button"
        onclick="addSnippet()"
        class="font-mono text-[12px] text-muted bg-transparent border border-border border-dashed rounded-md px-4 py-2 w-full cursor-pointer hover:border-border-md hover:text-text transition-colors"
    >
        + add code snippet
    </button>
</div>

<script>
(function () {

    // ── Config ──────────────────────────────────────────────────────────
    var CM_MODES = <?= json_encode($cmModes) ?>;
    var THEMES   = { 'vs-dark': 'dracula', 'monokai': 'monokai', 'dracula': 'dracula', 'solarized': 'default', 'default': 'default' };
    var editors  = {};
    var count    = <?= count($snippets) ?>;

    // ── Init existing editors ───────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        <?php foreach ($snippets as $i => $s): ?>
        initEditor(<?= $i ?>, '<?= $cmModes[$s->language] ?? 'null' ?>', '<?= isset($s->editor_theme) ? ($cmThemes[$s->editor_theme] ?? 'dracula') : 'dracula' ?>');
        <?php endforeach; ?>
    });

    function initEditor(i, mode, theme) {
        var ta   = document.getElementById('cm-ta-' + i);
        var host = document.getElementById('cm-host-' + i);
        if (!ta || !host || editors[i]) return;

        editors[i] = CodeMirror(host, {
            value:            ta.value,
            mode:             mode === 'null' ? null : mode,
            theme:            theme || 'dracula',
            lineNumbers:      true,
            matchBrackets:    true,
            autoCloseBrackets: true,
            autoRefresh:      true,
            indentUnit:       4,
            tabSize:          4,
            indentWithTabs:   false,
            extraKeys: {
                'Tab': function(cm) {
                    if (cm.somethingSelected()) { cm.indentSelection('add'); }
                    else { cm.replaceSelection('    ', 'end'); }
                }
            },
        });

        // Keep textarea in sync so the form submits the code value
        editors[i].on('change', function (cm) {
            ta.value = cm.getValue();
        });
    }

    // ── Toggle collapse ─────────────────────────────────────────────────
    window.toggleSnippet = function (i) {
        var body    = document.getElementById('snippet-body-' + i);
        var chevron = document.getElementById('chevron-' + i);
        var hidden  = body.style.display === 'none';
        body.style.display = hidden ? '' : 'none';
        chevron.style.transform = hidden ? '' : 'rotate(-90deg)';
        if (hidden && editors[i]) editors[i].refresh();
    };

    // ── Language change ─────────────────────────────────────────────────
    window.changeLanguage = function (i) {
        var sel  = document.getElementById('lang-select-' + i);
        var mode = CM_MODES[sel.value] || null;
        if (editors[i]) editors[i].setOption('mode', mode === 'null' ? null : mode);
    };

    // ── Theme change ────────────────────────────────────────────────────
    window.changeTheme = function (i) {
        var sel   = document.getElementById('theme-select-' + i);
        var theme = THEMES[sel.value] || 'dracula';
        if (editors[i]) editors[i].setOption('theme', theme);
    };

    // ── Remove ──────────────────────────────────────────────────────────
    window.removeSnippet = function (i) {
        if (!confirm('Remove this snippet?')) return;
        var card = document.getElementById('snippet-' + i);
        if (card) card.remove();
        delete editors[i];
    };

    // ── Add new ─────────────────────────────────────────────────────────
    window.addSnippet = function () {
        var i   = count++;
        var div = document.createElement('div');
        div.className = 'snippet-card';
        div.id        = 'snippet-' + i;
        div.innerHTML = `
            <div class="snippet-head" onclick="toggleSnippet(${i})">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none" id="chevron-${i}" style="transition:transform .15s">
                    <path d="M2 4l4 4 4-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
                <span class="snippet-head-label">New snippet</span>
                <span class="snippet-head-lang">TEXT</span>
                <button type="button" class="snippet-remove-btn" onclick="event.stopPropagation();removeSnippet(${i})">remove</button>
            </div>
            <div class="snippet-body" id="snippet-body-${i}">
                <input type="hidden" name="code[${i}][id]" value="">
                <div class="snippet-row-3">
                    <div class="snippet-field">
                        <label>Title</label>
                        <input type="text" name="code[${i}][title]" placeholder="e.g. Blink LED">
                    </div>
                    <div class="snippet-field">
                        <label>File name</label>
                        <input type="text" name="code[${i}][file_name]" placeholder="e.g. blink.ino">
                    </div>
                    <div class="snippet-field">
                        <label>Language</label>
                        <select name="code[${i}][language]" id="lang-select-${i}" onchange="changeLanguage(${i})">
                            <?php foreach ($languages as $key => $cfg): ?>
                            <option value="<?= $key ?>"><?= $cfg['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="snippet-field" style="margin-top:4px">
                    <label>Theme</label>
                    <select name="code[${i}][editor_theme]" id="theme-select-${i}" onchange="changeTheme(${i})" style="width:180px">
                        <?php foreach ($themes as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $key === 'vs-dark' ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="cm-wrap">
                    <textarea id="cm-ta-${i}" style="display:none" name="code[${i}][code]"></textarea>
                    <div id="cm-host-${i}"></div>
                </div>
            </div>`;

        document.getElementById('snippets-container').appendChild(div);
        setTimeout(function () {
            initEditor(i, null, 'dracula');
        }, 50);
    };

})();
</script>
