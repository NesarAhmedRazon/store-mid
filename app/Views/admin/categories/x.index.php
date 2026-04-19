<?php

/**
 * views/categories/index.php
 *
 * Expects:
 *   $categories — flat array of category objects sorted by path (from CategoryModel::getTree())
 *   $title      — page title string
 */
?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="flex items-center gap-2.5 bg-down-bg border border-down/20 text-down text-[13px] px-4 py-3 rounded-lg mb-5">
        <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
            <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4" />
            <path d="M8 5v3.5M8 11h.01" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
        </svg>
        <?= esc(session()->getFlashdata('error')) ?>
    </div>
<?php endif;
// usort($categories, fn($a, $b) => strcmp($a->path, $b->path));


?>
<style>
    .scroll-container {
        height: 75vh;
        overflow: auto;
        position: relative;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    td {
        padding: 10px 12px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .row-name {
        display: flex;
        align-items: center;
        gap: 6px;
    }
</style>

<div class="card overflow-hidden">
    <!-- Toolbar -->
    <div class="p-3 border-b flex items-center justify-between gap-3"><input id="cat-search" type="text" placeholder="Search categories..." class="border px-3 py-1 text-sm rounded w-64" />
        <div class="text-sm text-gray-500">Total: <?= count($categories) ?></div>
    </div>
    <!-- Scroll container -->
    <div id="scroll-container" class="scroll-container">
        <div id="spacer-top"></div>
        <table class="w-full text-sm">── <tbody id="tbody"></tbody>
        </table>
        <div id="spacer-bottom"></div>
    </div>
</div>

<script>
    window.categories = <?= json_encode($categories) ?>;

    const ROW_HEIGHT = 44;
    const BUFFER = 10;

    let data = window.categories || [];
    let filtered = [...data];
    console.log(data);
    const container = document.getElementById("scroll-container");
    const tbody = document.getElementById("tbody");
    const spacerTop = document.getElementById("spacer-top");
    const spacerBottom = document.getElementById("spacer-bottom");

    container.addEventListener("scroll", render);
    render();

    function render() {
        const scrollTop = container.scrollTop;
        const height = container.clientHeight;

        const start = Math.max(0, Math.floor(scrollTop / ROW_HEIGHT) - BUFFER);
        const end = Math.min(
            filtered.length,
            Math.ceil((scrollTop + height) / ROW_HEIGHT) + BUFFER
        );

        const visible = filtered.slice(start, end);

        spacerTop.style.height = start * ROW_HEIGHT + "px";
        spacerBottom.style.height = (filtered.length - end) * ROW_HEIGHT + "px";

        tbody.innerHTML = visible.map(renderRow).join("");
    }

    function renderRow(cat) {
        return `
    <tr>
      <td>
        <div class="row-name" style="padding-left:${cat.depth * 18}px">

          <span style="color:#999">├</span>

          <a href="/products/categories/${cat.id}" class="font-medium">
            ${escapeHtml(cat.name)}
          </a>

          <span class="text-xs text-gray-400">
            (${cat.product_count})
          </span>

        </div>
      </td>

      <td class="hidden sm:table-cell text-gray-500">
        ${cat.slug}
      </td>

      <td class="text-center">
        ${cat.product_count}
      </td>

      <td class="hidden md:table-cell text-center text-gray-400">
        ${cat.path}
      </td>
    </tr>
  `;
    }

    function escapeHtml(str) {
        return (str || "").replace(/[&<>"']/g, m => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        } [m]));
    }

    document.getElementById("cat-search").addEventListener("input", (e) => {
        const q = e.target.value.toLowerCase();

        filtered = data.filter(c =>
            c.name.toLowerCase().includes(q) ||
            c.slug.toLowerCase().includes(q) ||
            c.path.toLowerCase().includes(q)
        );

        container.scrollTop = 0;
        render();
    });
</script><?= $this->endSection() ?>