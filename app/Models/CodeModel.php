<?php

namespace App\Models;

use CodeIgniter\Model;

class CodeModel extends Model
{
    protected $table      = 'programming';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'product_id',
        'title',
        'language',
        'file_name',
        'file_dir',
        'code',
        'editor_theme',
        'sort_order',
    ];

    protected $useTimestamps = true;

    // Supported languages — used for validation and editor mode mapping
    public const LANGUAGES = [
        'arduino'     => ['label' => 'Arduino',      'cm_mode' => 'text/x-csrc'],
        'micropython' => ['label' => 'MicroPython',  'cm_mode' => 'python'],
        'python'      => ['label' => 'Python',       'cm_mode' => 'python'],
        'c'           => ['label' => 'C',            'cm_mode' => 'text/x-csrc'],
        'cpp'         => ['label' => 'C++',          'cm_mode' => 'text/x-c++src'],
        'javascript'  => ['label' => 'JavaScript',   'cm_mode' => 'javascript'],
        'jsx'         => ['label' => 'JSX / React',  'cm_mode' => 'jsx'],
        'typescript'  => ['label' => 'TypeScript',   'cm_mode' => 'text/typescript'],
        'php'         => ['label' => 'PHP',          'cm_mode' => 'application/x-httpd-php'],
        'html'        => ['label' => 'HTML',         'cm_mode' => 'htmlmixed'],
        'css'         => ['label' => 'CSS',          'cm_mode' => 'css'],
        'bash'        => ['label' => 'Bash / Shell', 'cm_mode' => 'shell'],
        'rust'        => ['label' => 'Rust',         'cm_mode' => 'rust'],
        'lua'         => ['label' => 'Lua',          'cm_mode' => 'lua'],
        'makefile'    => ['label' => 'Makefile',     'cm_mode' => 'cmake'],
        'text'        => ['label' => 'Plain text',   'cm_mode' => 'null'],
    ];

    public const THEMES = [
        'vs-dark'   => 'VS Dark',
        'monokai'   => 'Monokai',
        'dracula'   => 'Dracula',
        'solarized' => 'Solarized',
        'default'   => 'Light',
    ];

    // ── Queries ────────────────────────────────────────────────────────────

    /**
     * Get all code snippets for a product, ordered by sort_order.
     */
    public function getByProduct(int $productId): array
    {
        return $this->where('product_id', $productId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /**
     * Sync snippets for a product from a POST payload array.
     *
     * Deletes removed snippets, updates existing ones, inserts new ones.
     * Each item in $snippets: [id?, title, language, file_name, code, editor_theme, sort_order]
     *
     * @param int   $productId
     * @param array $snippets  Raw POST data from the form
     */
    public function syncForProduct(int $productId, array $snippets): void
    {
        $existingIds    = array_column($this->getByProduct($productId), 'id');
        $submittedIds   = [];

        foreach ($snippets as $order => $snippet) {
            if (empty(trim($snippet['code'] ?? ''))) {
                continue; // skip blank entries
            }

            $payload = [
                'product_id'   => $productId,
                'title'        => trim($snippet['title']       ?? ''),
                'language'     => $snippet['language']         ?? 'text',
                'file_name'    => trim($snippet['file_name']   ?? ''),
                'file_dir'    => trim($snippet['file_dir']   ?? ''),
                'code'         => $snippet['code'],
                'editor_theme' => $snippet['editor_theme']     ?? 'vs-dark',
                'sort_order'   => (int) $order,
            ];

            $snippetId = !empty($snippet['id']) ? (int) $snippet['id'] : null;

            if ($snippetId && in_array($snippetId, $existingIds, true)) {
                $this->update($snippetId, $payload);
                $submittedIds[] = $snippetId;
            } else {
                $newId = $this->insert($payload, true);
                $submittedIds[] = $newId;
            }
        }

        // Delete snippets that were removed in the form
        $toDelete = array_diff($existingIds, $submittedIds);
        if (!empty($toDelete)) {
            $this->whereIn('id', array_values($toDelete))->delete();
        }
    }

    /**
     * Format snippets for the API response.
     */
    public function formatForApi(array $snippets): array
    {
        return array_map(fn($s) => [
            'title'        => $s->title,
            'language'     => $s->language,
            'file_name'    => $s->file_name,
            'file_dir'     => $s->file_dir,
            'editor_theme' => $s->editor_theme,
            'code'         => $s->code,
        ], $snippets);
    }
}