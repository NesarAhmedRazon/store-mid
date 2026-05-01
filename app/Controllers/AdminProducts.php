<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\MetaModel;
use App\Libraries\AttributeService;
use App\Models\CategoryModel;
use App\Libraries\ProductFetcher;

class AdminProducts extends BaseController
{
    protected ProductModel $model;
    protected MetaModel    $meta;

    public function __construct()
    {
        $this->model = new ProductModel();
        $this->meta  = new MetaModel();
    }

    // ── Index ─────────────────────────────────────────────────────────────

    public function index()
    {
        $products = $this->model
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        return view('products/index', [
            'title'    => 'Products',
            'products' => $products,
        ]);
    }

    // ── Preview ───────────────────────────────────────────────────────────

    public function preview()
    {
        $id = (int) $this->request->getGet('id');

        if (!$id) {
            return redirect()->to('/products')->with('error', 'Invalid product ID.');
        }

        $productFetcher = new ProductFetcher();
        $product = (object) $productFetcher->getProduct($id, [
            'mode'     => 'full',
            'internal' => true,
        ]);

        if (!$product) {
            return redirect()->to('/products')->with('error', 'Product not found.');
        }

        $attr = new AttributeService();
        $product->attributes = $attr->getByProductId($product->id);

        $cats = new CategoryModel();
        $product->categories = $cats->getByProduct($product->id);

        $mediaModel      = new \App\Models\MediaModel();
        $media           = $mediaModel->getForEntity('product', $product->id);
        $product->thumb  = $media['thumbnail'];
        $product->gallery = $media['gallery'];

        return view('products/preview', [
            'title'   => $product->title,
            'product' => $product,
        ]);
    }

    // ── Create — GET /products/create ─────────────────────────────────────

    public function create()
    {
        return view('products/form', [
            'title'      => 'New product',
            'mode'       => 'create',
            'product'    => null,
            'meta'       => [],
            'formAction' => '/products/store',
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    // ── Store — POST /products/store ──────────────────────────────────────

    public function store()
    {
        $data = $this->request->getPost();

        if (empty($data['title'])) {
            session()->setFlashdata('errors', ['Title is required.']);
            return redirect()->back()->withInput();
        }

        $productData = [
            'title'          => trim($data['title']),
            'sku'            => $data['sku']          ?? null,
            'permalink'      => $data['permalink']    ?? null,
            'wc_id'          => $data['wc_id']        ?: null,
            'stock_status'   => $data['stock_status'] ?? 'outofstock',
            'stock_quantity' => $data['stock_quantity'] ?: null,
            'price_regular'  => $data['price_regular'] ?: 0,
            'price_offer'    => $data['price_offer']   ?: null,
            'price_buy'      => $data['price_buy']     ?: null,
            'wc_created_at'  => $data['wc_created_at'] ?: null,
        ];

        $id = $this->model->insert($productData, true);

        if (!$id) {
            session()->setFlashdata('errors', $this->model->errors());
            return redirect()->back()->withInput();
        }

        $this->saveMeta($id, $data);

        session()->setFlashdata('success', 'Product created.');
        return redirect()->to("/products/preview?id={$id}");
    }

    // ── Edit — GET /products/edit?id=1 ───────────────────────────────────

    public function edit()
    {
        $id = (int) $this->request->getGet('id');

        if (!$id) {
            return redirect()->to('/products')->with('error', 'Invalid product ID.');
        }

        $product = $this->model->find($id);
        if (!$product) {
            return redirect()->to('/products')->with('error', 'Product not found.');
        }

        $meta = $this->meta->getMap(MetaModel::ENTITY_PRODUCT, $id);

        return view('products/form', [
            'title'      => 'Edit — ' . $product->title,
            'mode'       => 'edit',
            'product'    => $product,
            'meta'       => $meta,
            'formAction' => "/products/update?id={$id}",
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
    }

    // ── Update — POST /products/update?id=1 ──────────────────────────────

    public function update($id)
    {
        $id = (int) $this->request->getGet('id');

        $product = $this->model->find($id);
        if (!$product) {
            return redirect()->to('/products')->with('error', 'Product not found.');
        }

        $data = $this->request->getPost();

        if (empty($data['title'])) {
            session()->setFlashdata('errors', ['Title is required.']);
            return redirect()->back()->withInput();
        }

        $productData = [
            'title'          => trim($data['title']),
            'sku'            => $data['sku']            ?? $product->sku,
            'permalink'      => $data['permalink']      ?? $product->permalink,
            'wc_id'          => $data['wc_id']          ?: $product->wc_id,
            'stock_status'   => $data['stock_status']   ?? $product->stock_status,
            'stock_quantity' => $data['stock_quantity'] ?: $product->stock_quantity,
            'price_regular'  => $data['price_regular']  ?: $product->price_regular,
            'price_offer'    => $data['price_offer']    ?: null,
            'price_buy'      => $data['price_buy']      ?: null,
            'wc_created_at'  => $data['wc_created_at']  ?: $product->wc_created_at,
        ];

        if (!$this->model->update($id, $productData)) {
            session()->setFlashdata('errors', $this->model->errors());
            return redirect()->back()->withInput();
        }

        $this->saveMeta($id, $data);

        session()->setFlashdata('success', 'Product updated.');
        return redirect()->to("/products/preview?id={$id}");
    }

    // ── Meta save helper ──────────────────────────────────────────────────

    /**
     * Reads meta_* fields from the POST payload and persists them.
     *
     * Handled keys:
     *   meta_title_bn        → slug: title_bn
     *   meta_seo_title       → slug: seo (JSON { title, description })
     *   meta_seo_description → merged into seo JSON above
     */
    private function saveMeta(int $productId, array $data): void
    {
        // title_bn
        if (isset($data['meta_title_bn'])) {
            $this->meta->put(
                MetaModel::ENTITY_PRODUCT,
                $productId,
                'title_bn',
                'বাংলা টাইটেল',
                trim($data['meta_title_bn']) ?: null
            );
        }

        // seo — stored as a single JSON blob under slug "seo"
        $seoTitle = trim($data['meta_seo_title']       ?? '');
        $seoDesc  = trim($data['meta_seo_description'] ?? '');

        if ($seoTitle || $seoDesc) {
            // Read existing seo meta so we don't overwrite keys we're not touching
            $existing = $this->meta->get(MetaModel::ENTITY_PRODUCT, $productId, 'seo', []);
            $existing = is_array($existing) ? $existing : [];

            $seoPayload = array_merge($existing, array_filter([
                'title'       => $seoTitle ?: null,
                'description' => $seoDesc  ?: null,
            ]));

            $this->meta->put(
                MetaModel::ENTITY_PRODUCT,
                $productId,
                'seo',
                'SEO',
                json_encode($seoPayload)
            );
        }
    }
}