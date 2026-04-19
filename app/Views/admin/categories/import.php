<?php
/**
 * views/admin/categories/import.php
 * Import / sync categories from a JSON file or pasted payload.
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php if (session()->getFlashdata('error')): ?>
<div class="flex items-center gap-2.5 bg-down-bg border border-down/20 text-down text-[13px] px-4 py-3 rounded-lg mb-5">
    <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
    <?= esc(session()->getFlashdata('error')) ?>
</div>
<?php endif; ?>

<div class="flex items-center justify-between mb-5">
    <a href="/products/categories"
       class="text-[11px] font-mono text-subtle no-underline hover:text-text transition-colors">
        ← back to categories
    </a>
</div>

<div class="max-w-2xl flex flex-col gap-4">

    <div class="card">
        <div class="card-head">
            <span class="card-title">Import Categories</span>
        </div>
        <div class="p-4 flex flex-col gap-2 text-[12px] font-mono text-subtle">
            <p>Accepts the <code class="text-text">/api/get/categories</code> response envelope or a bare array.</p>
            <p>Matches on <code class="text-text">id</code> (wc_id) — inserts new, updates existing.</p>
            <p>Parent–child hierarchy is resolved automatically in two passes, so order in the JSON does not matter.</p>
        </div>
    </div>

    <!-- Upload form -->
    <form action="/products/categories/import" method="POST" enctype="multipart/form-data"
          class="flex flex-col gap-4" id="import-form">
        <?= csrf_field() ?>

        <!-- Tab toggle -->
        <div class="flex gap-0 border border-border rounded-md overflow-hidden w-fit text-[11px] font-mono">
            <button type="button" id="tab-file"
                    onclick="switchTab('file')"
                    class="px-4 py-1.5 bg-bg text-text border-r border-border cursor-pointer">
                upload file
            </button>
            <button type="button" id="tab-paste"
                    onclick="switchTab('paste')"
                    class="px-4 py-1.5 bg-transparent text-subtle hover:text-text cursor-pointer">
                paste json
            </button>
        </div>

        <!-- File input -->
        <div id="panel-file" class="card">
            <div class="p-4 flex flex-col gap-3">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">JSON File</label>
                <div id="drop-zone"
                     class="border-2 border-dashed border-border rounded-lg p-8 flex flex-col items-center gap-2 cursor-pointer hover:border-border-md transition-colors"
                     onclick="document.getElementById('json_file').click()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" class="text-subtle">
                        <path d="M12 4v12m0-12L8 8m4-4l4 4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                    </svg>
                    <span class="text-[12px] font-mono text-subtle" id="drop-label">click to choose or drag & drop a .json file</span>
                </div>
                <input type="file" name="json_file" id="json_file" accept=".json,application/json"
                       class="hidden">
            </div>
        </div>

        <!-- Paste input -->
        <div id="panel-paste" class="card hidden">
            <div class="p-4 flex flex-col gap-3">
                <label class="text-[10px] uppercase tracking-widest font-mono text-subtle">Paste JSON</label>
                <textarea name="json_raw" id="json-raw" rows="14" placeholder='{"status":"ok","categories":[...]}'
                          class="text-[12px] font-mono px-3 py-2 border border-border rounded-md bg-transparent text-text placeholder:text-subtle focus:outline-none focus:border-border-md resize-y"></textarea>
                <div class="flex gap-2">
                    <button type="button" onclick="formatJson()"
                            class="text-[11px] font-mono text-subtle hover:text-text px-3 py-1 border border-border rounded-md bg-transparent cursor-pointer transition-colors">
                        format
                    </button>
                    <button type="button" onclick="clearJson()"
                            class="text-[11px] font-mono text-subtle hover:text-text px-3 py-1 border border-border rounded-md bg-transparent cursor-pointer transition-colors">
                        clear
                    </button>
                </div>
            </div>
        </div>

        <!-- Submit -->
        <div class="flex items-center justify-between">
            <span class="text-[10px] font-mono text-subtle" id="file-status"></span>
            <button type="submit" id="submit-btn"
                    class="text-[11px] font-mono text-text px-5 py-2 border border-border-md rounded-md hover:bg-bg transition-colors cursor-pointer bg-transparent">
                import & sync →
            </button>
        </div>

    </form>

</div>

<script>
let activeTab = 'file';

function switchTab(tab) {
    activeTab = tab;

    document.getElementById('panel-file').classList.toggle('hidden',  tab !== 'file');
    document.getElementById('panel-paste').classList.toggle('hidden', tab !== 'paste');

    document.getElementById('tab-file').classList.toggle('bg-bg',          tab === 'file');
    document.getElementById('tab-file').classList.toggle('text-text',      tab === 'file');
    document.getElementById('tab-paste').classList.toggle('bg-bg',         tab === 'paste');
    document.getElementById('tab-paste').classList.toggle('text-text',     tab === 'paste');
    document.getElementById('tab-file').classList.toggle('text-subtle',    tab !== 'file');
    document.getElementById('tab-paste').classList.toggle('text-subtle',   tab !== 'paste');
}

// File drop zone
const dropZone  = document.getElementById('drop-zone');
const fileInput = document.getElementById('json_file');
const dropLabel = document.getElementById('drop-label');
const status    = document.getElementById('file-status');

fileInput.addEventListener('change', function () {
    if (this.files[0]) {
        dropLabel.textContent = this.files[0].name;
        status.textContent    = (this.files[0].size / 1024).toFixed(1) + ' KB';
    }
});

dropZone.addEventListener('dragover',  e => { e.preventDefault(); dropZone.classList.add('border-border-md'); });
dropZone.addEventListener('dragleave', ()  => dropZone.classList.remove('border-border-md'));
dropZone.addEventListener('drop', function (e) {
    e.preventDefault();
    dropZone.classList.remove('border-border-md');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    if (!file.name.endsWith('.json')) {
        status.textContent = 'Only .json files are accepted';
        return;
    }
    // Transfer dropped file to the real input
    const dt = new DataTransfer();
    dt.items.add(file);
    fileInput.files = dt.files;
    dropLabel.textContent = file.name;
    status.textContent    = (file.size / 1024).toFixed(1) + ' KB';
});

// Before submit: clear the inactive panel's input to avoid sending both
document.getElementById('import-form').addEventListener('submit', function () {
    if (activeTab === 'paste') {
        fileInput.value = '';     // don't send empty file input
    } else {
        document.getElementById('json-raw').value = '';
    }
});

function formatJson() {
    const ta = document.getElementById('json-raw');
    try { ta.value = JSON.stringify(JSON.parse(ta.value), null, 2); } catch (e) { /* invalid — leave as-is */ }
}

function clearJson() {
    document.getElementById('json-raw').value = '';
}
</script>

<?= $this->endSection() ?>