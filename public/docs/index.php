<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SMDPicker — API Reference</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#F4F3EF;--surface:#FAFAF8;--border:#E2E0D9;--border-md:#C8C6BC;
  --text:#1A1917;--muted:#78766E;--subtle:#A8A69E;
  --up:#1A6B3C;--up-bg:#E6F4EC;--down:#9B2020;--down-bg:#FAEAEA;
  --warn:#7A4E00;--warn-bg:#FEF3DC;--info:#0F4C8A;--info-bg:#E8F0FB;
  --mono:'IBM Plex Mono',monospace;--sans:'IBM Plex Sans',sans-serif;
}
body{font-family:var(--sans);background:var(--bg);color:var(--text);font-size:14px;line-height:1.6;display:flex;min-height:100vh}

/* Sidebar */
.sidebar{width:240px;shrink:0;background:var(--surface);border-right:1px solid var(--border);position:fixed;top:0;left:0;bottom:0;overflow-y:auto;padding:0}
.sidebar-brand{padding:20px;border-bottom:1px solid var(--border)}
.sidebar-brand h1{font-family:var(--mono);font-size:13px;font-weight:500}
.sidebar-brand p{font-size:11px;color:var(--subtle);margin-top:3px}
.sidebar nav{padding:12px 10px}
.nav-group{font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:var(--subtle);padding:12px 10px 4px}
.nav-group:first-child{padding-top:4px}
.nav-link{display:block;padding:6px 10px;font-family:var(--mono);font-size:12px;color:var(--muted);text-decoration:none;border-radius:5px;transition:background .1s,color .1s}
.nav-link:hover{background:var(--bg);color:var(--text)}
.nav-link.active{background:var(--text);color:#fff}

/* Content */
.content{margin-left:240px;flex:1;padding:40px 48px;max-width:900px}
.content h1{font-size:22px;font-weight:500;margin-bottom:6px}
.content>p{color:var(--muted);margin-bottom:32px;font-size:14px}

/* Sections */
section{margin-bottom:56px}
section h2{font-size:16px;font-weight:500;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border)}
section h3{font-family:var(--mono);font-size:13px;font-weight:500;margin:24px 0 8px;color:var(--text)}

/* Endpoint block */
.endpoint{background:var(--surface);border:1px solid var(--border);border-radius:8px;overflow:hidden;margin-bottom:24px}
.endpoint-head{display:flex;align-items:center;gap:10px;padding:14px 18px;border-bottom:1px solid var(--border)}
.method{font-family:var(--mono);font-size:11px;font-weight:500;padding:3px 8px;border-radius:4px;background:var(--up-bg);color:var(--up)}
.method.post{background:var(--info-bg);color:var(--info)}
.path{font-family:var(--mono);font-size:13px;color:var(--text)}
.endpoint-body{padding:18px}
.endpoint-body p{color:var(--muted);font-size:13px;margin-bottom:14px}

/* Auth badge */
.auth-badge{display:inline-flex;align-items:center;gap:5px;font-family:var(--mono);font-size:10px;background:var(--warn-bg);color:var(--warn);padding:2px 8px;border-radius:3px;margin-left:auto}

/* Tables */
table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:16px}
th{text-align:left;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--subtle);font-weight:500;padding:8px 12px;border-bottom:1px solid var(--border);background:var(--bg)}
td{padding:8px 12px;border-bottom:1px solid var(--border);vertical-align:top}
tr:last-child td{border-bottom:none}
td code{font-family:var(--mono);font-size:11px;background:var(--bg);border:1px solid var(--border);padding:1px 5px;border-radius:3px}
td.opt{color:var(--subtle)}

/* Code blocks */
pre{background:var(--text);color:#E8E6E0;font-family:var(--mono);font-size:12px;padding:16px 18px;border-radius:6px;overflow-x:auto;line-height:1.7;margin-bottom:16px}
pre .k{color:#A8D8A8}  /* key */
pre .s{color:#F0C888}  /* string */
pre .n{color:#88CCEE}  /* number */
pre .c{color:#888}     /* comment */
code{font-family:var(--mono);font-size:12px}

/* Inline badges */
.badge{font-family:var(--mono);font-size:10px;padding:2px 7px;border-radius:3px;font-weight:500}
.badge-required{background:var(--down-bg);color:var(--down)}
.badge-optional{background:var(--bg);color:var(--subtle);border:1px solid var(--border)}
.badge-default{background:var(--info-bg);color:var(--info)}
.badge-get{background:var(--up-bg);color:var(--up)}

/* Notes */
.note{background:var(--info-bg);border:1px solid rgba(15,76,138,.2);color:var(--info);border-radius:6px;padding:12px 16px;font-size:12px;margin-bottom:16px}
.note strong{display:block;margin-bottom:3px;font-size:11px;text-transform:uppercase;letter-spacing:.06em}
.warn-note{background:var(--warn-bg);border:1px solid rgba(122,78,0,.2);color:var(--warn)}

/* Mode chips */
.modes{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.chip{font-family:var(--mono);font-size:11px;padding:3px 10px;border-radius:3px;border:1px solid var(--border);color:var(--muted)}
.chip.active{background:var(--text);color:#fff;border-color:var(--text)}
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <h1>SMDPicker API</h1>
    <p>Frontend Reference · v1</p>
  </div>
  <nav>
    <div class="nav-group">Getting started</div>
    <a href="#auth" class="nav-link">Authentication</a>
    <a href="#modes" class="nav-link">Response modes</a>
    <a href="#errors" class="nav-link">Errors</a>

    <div class="nav-group">Products</div>
    <a href="#all-products" class="nav-link">All products</a>
    <a href="#single-product" class="nav-link">Single product</a>

    <div class="nav-group">Categories</div>
    <a href="#all-categories" class="nav-link">All categories</a>
    <a href="#single-category" class="nav-link">Category by slug</a>
  </nav>
</aside>

<main class="content">

  <h1>SMDPicker API Reference</h1>
  <p>Base URL: <code>https://your-domain.com/api/get</code> &nbsp;·&nbsp; All responses are JSON &nbsp;·&nbsp; All endpoints require authentication.</p>

  <!-- ── Auth ── -->
  <section id="auth">
    <h2>Authentication</h2>
    <p style="color:var(--muted);font-size:13px;margin-bottom:14px">
      Every request must include the shared secret in the request header. Contact the backend team for your secret value.
    </p>
    <table>
      <thead><tr><th>Header</th><th>Value</th></tr></thead>
      <tbody>
        <tr>
          <td><code>x-front-webhook-secret</code></td>
          <td>Your shared secret string</td>
        </tr>
      </tbody>
    </table>

    <h3>Example — fetch with auth header</h3>
    <pre><span class="c">// JavaScript</span>
const res = await fetch('<span class="s">https://your-domain.com/api/get/products</span>', {
  headers: {
    '<span class="k">x-front-webhook-secret</span>': '<span class="s">your-secret-here</span>',
  },
});
const data = await res.json();</pre>

    <div class="note warn-note">
      <strong>Missing or invalid header</strong>
      Returns <code>401 Unauthorized</code> with <code>{"status":"error","message":"Invalid or missing webhook secret"}</code>
    </div>
  </section>

  <!-- ── Modes ── -->
  <section id="modes">
    <h2>Response modes</h2>
    <p style="color:var(--muted);font-size:13px;margin-bottom:14px">
      All list and single-item endpoints accept a <code>mode</code> query parameter that controls how much data is returned. Use the lightest mode your UI needs.
    </p>
    <table>
      <thead><tr><th>Mode</th><th>Returns</th><th>Best for</th></tr></thead>
      <tbody>
        <tr><td><code>minimal</code></td><td>ID, title, permalink, updated_at</td><td>Search suggestions, sitemaps</td></tr>
        <tr><td><code>summary</code></td><td>Minimal + thumbnail, price, stock, brand, package, categories</td><td>Product cards, listing pages</td></tr>
        <tr><td><code>full</code></td><td>Summary + all attributes, gallery, all metadata, SEO fields</td><td>Product detail pages</td></tr>
      </tbody>
    </table>
    <p style="font-size:12px;color:var(--subtle)">Default is <code>full</code> on all endpoints unless noted otherwise.</p>
  </section>

  <!-- ── Errors ── -->
  <section id="errors">
    <h2>Errors</h2>
    <table>
      <thead><tr><th>HTTP status</th><th>When</th><th>Response body</th></tr></thead>
      <tbody>
        <tr><td><code>401</code></td><td>Auth header missing or wrong</td><td><code>{"status":"error","message":"Invalid or missing webhook secret"}</code></td></tr>
        <tr><td><code>400</code></td><td>Missing required parameter</td><td><code>{"status":"error","message":"..."}</code></td></tr>
        <tr><td><code>404</code></td><td>Resource not found</td><td><code>{"status":"error","message":"Product 'xyz' not found."}</code></td></tr>
        <tr><td><code>500</code></td><td>Server error</td><td><code>{"status":"error","message":"An error occurred..."}</code></td></tr>
      </tbody>
    </table>
  </section>

  <!-- ─────────────────────── PRODUCTS ─────────────────────── -->

  <!-- ── All products ── -->
  <section id="all-products">
    <h2>Products</h2>

    <div class="endpoint">
      <div class="endpoint-head">
        <span class="method badge-get">GET</span>
        <span class="path">/api/get/products</span>
        <span class="auth-badge">🔑 auth required</span>
      </div>
      <div class="endpoint-body">
        <p>Returns a paginated list of products. Use <code>mode</code> to control the payload size.</p>

        <h3>Query parameters</h3>
        <table>
          <thead><tr><th>Param</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
          <tbody>
            <tr>
              <td><code>mode</code></td>
              <td>string</td>
              <td><code>full</code></td>
              <td><code>minimal</code> · <code>summary</code> · <code>full</code></td>
            </tr>
            <tr>
              <td><code>page</code></td>
              <td>integer</td>
              <td><code>1</code></td>
              <td>Page number. Ignored when <code>perPage=all</code></td>
            </tr>
            <tr>
              <td><code>perPage</code></td>
              <td>integer · <code>"all"</code></td>
              <td><code>20</code></td>
              <td>Results per page. Pass <code>all</code> to skip pagination</td>
            </tr>
            <tr>
              <td><code>category</code></td>
              <td>string</td>
              <td>—</td>
              <td>Filter by category slug. Includes all descendant categories</td>
            </tr>
          </tbody>
        </table>

        <h3>Response</h3>
        <pre>{
  <span class="k">"status"</span>:  <span class="s">"ok"</span>,
  <span class="k">"mode"</span>:    <span class="s">"summary"</span>,
  <span class="k">"page"</span>:    <span class="n">1</span>,
  <span class="k">"perPage"</span>: <span class="n">20</span>,
  <span class="k">"total"</span>:   <span class="n">143</span>,
  <span class="k">"data"</span>: [
    {
      <span class="k">"id"</span>:           <span class="n">131</span>,
      <span class="k">"title"</span>:        <span class="s">"CM4040M00012001 40MHz Crystal Oscillator"</span>,
      <span class="k">"permalink"</span>:    <span class="s">"/product/cm4040m00012001-40mhz-crystal-oscillator"</span>,
      <span class="k">"sku"</span>:          <span class="s">"CM4040M00012001"</span>,
      <span class="k">"stock"</span>: {
        <span class="k">"status"</span>:   <span class="s">"instock"</span>,
        <span class="k">"quantity"</span>: <span class="n">500</span>
      },
      <span class="k">"price"</span>: {
        <span class="k">"regular"</span>: <span class="n">250.00</span>,
        <span class="k">"offer"</span>:   <span class="n">220.00</span>,
        <span class="k">"cost"</span>:    <span class="n">null</span>   <span class="c">// only present in full mode</span>
      },
      <span class="k">"thumbnail"</span>:  <span class="s">"https://..."</span>,
      <span class="k">"updated_at"</span>: <span class="s">"2026-04-28T10:22:00"</span>
    }
  ]
}</pre>

        <h3>Examples</h3>
        <pre><span class="c">// Listing page — summary, page 2</span>
GET /api/get/products?mode=summary&page=2&perPage=24

<span class="c">// Filter by category</span>
GET /api/get/products?mode=summary&category=crystal-oscillators

<span class="c">// All products at once (no pagination)</span>
GET /api/get/products?mode=minimal&perPage=all</pre>
      </div>
    </div>
  </section>

  <!-- ── Single product ── -->
  <section id="single-product">
    <div class="endpoint">
      <div class="endpoint-head">
        <span class="method badge-get">GET</span>
        <span class="path">/api/get/product/<strong>{id_or_slug}</strong></span>
        <span class="auth-badge">🔑 auth required</span>
      </div>
      <div class="endpoint-body">
        <p>Returns a single product. The identifier can be either the internal numeric ID or the URL slug.</p>

        <h3>Identifier formats</h3>
        <table>
          <thead><tr><th>Format</th><th>Example</th><th>Lookup method</th></tr></thead>
          <tbody>
            <tr><td>Numeric ID</td><td><code>/api/get/product/131</code></td><td>Matches <code>products.id</code></td></tr>
            <tr><td>Slug</td><td><code>/api/get/product/cm4040m00012001-40mhz-crystal-oscillator</code></td><td>Matches trailing segment of <code>products.permalink</code></td></tr>
          </tbody>
        </table>

        <h3>Query parameters</h3>
        <table>
          <thead><tr><th>Param</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
          <tbody>
            <tr>
              <td><code>mode</code></td>
              <td>string</td>
              <td><code>full</code></td>
              <td><code>minimal</code> · <code>summary</code> · <code>full</code></td>
            </tr>
          </tbody>
        </table>

        <h3>Response — <code>mode=full</code></h3>
        <pre>{
  <span class="k">"status"</span>: <span class="s">"ok"</span>,
  <span class="k">"mode"</span>:   <span class="s">"full"</span>,
  <span class="k">"product"</span>: {
    <span class="k">"id"</span>:        <span class="n">131</span>,
    <span class="k">"wc_id"</span>:    <span class="n">1842</span>,
    <span class="k">"title"</span>:    <span class="s">"CM4040M00012001 40MHz Crystal Oscillator"</span>,
    <span class="k">"permalink"</span>:<span class="s">"/product/cm4040m00012001-40mhz-crystal-oscillator"</span>,
    <span class="k">"sku"</span>:      <span class="s">"CM4040M00012001"</span>,

    <span class="k">"price"</span>: {
      <span class="k">"regular"</span>: <span class="n">250.00</span>,
      <span class="k">"offer"</span>:   <span class="n">220.00</span>,
      <span class="k">"cost"</span>:    <span class="n">85.00</span>
    },

    <span class="k">"stock"</span>: {
      <span class="k">"status"</span>:   <span class="s">"instock"</span>,
      <span class="k">"quantity"</span>: <span class="n">500</span>
    },

    <span class="k">"thumbnail"</span>: <span class="s">"https://..."</span>,
    <span class="k">"gallery"</span>: [
      <span class="s">"https://..."</span>, <span class="s">"https://..."</span>
    ],

    <span class="k">"attributes"</span>: {
      <span class="s">"Frequency"</span>:           [<span class="s">"40 MHz"</span>],
      <span class="s">"Package"</span>:             [<span class="s">"SMD 4-Pad"</span>],
      <span class="s">"Load Capacitance"</span>:    [<span class="s">"12 pF"</span>],
      <span class="s">"Operating Temp."</span>:     [<span class="s">"-20°C to +70°C"</span>],
      <span class="s">"Supply Voltage"</span>:      [<span class="s">"3.3V"</span>]
    },

    <span class="k">"categories"</span>: [
      {
        <span class="k">"name"</span>:       <span class="s">"Crystal Oscillators"</span>,
        <span class="k">"slug"</span>:       <span class="s">"crystal-oscillators"</span>,
        <span class="k">"permalink"</span>:  <span class="s">"electronic-components/passive/crystal-oscillators"</span>,
        <span class="k">"is_primary"</span>: <span class="n">true</span>
      }
    ],

    <span class="k">"metadata"</span>: {
      <span class="k">"title_bn"</span>: <span class="s">"৪০ মেগাহার্টজ ক্রিস্টাল অসিলেটর"</span>,
      <span class="k">"seo"</span>: {
        <span class="k">"title"</span>:       <span class="s">"Buy 40MHz Crystal Oscillator CM4040M | SMDPicker"</span>,
        <span class="k">"description"</span>: <span class="s">"40MHz SMD Crystal Oscillator, 12pF load..."</span>
      }
    },

    <span class="k">"wc_created_at"</span>: <span class="s">"2025-11-14T08:00:00"</span>,
    <span class="k">"updated_at"</span>:    <span class="s">"2026-04-28T10:22:00"</span>
  }
}</pre>

        <div class="note">
          <strong>Attributes shape</strong>
          Attributes are grouped by attribute name. Each key maps to an array of values — a single product can have multiple values for one attribute (e.g. multiple package options).
        </div>

        <h3>Response — <code>mode=minimal</code></h3>
        <pre>{
  <span class="k">"status"</span>: <span class="s">"ok"</span>,
  <span class="k">"mode"</span>:   <span class="s">"minimal"</span>,
  <span class="k">"product"</span>: {
    <span class="k">"id"</span>:         <span class="n">131</span>,
    <span class="k">"title"</span>:      <span class="s">"CM4040M00012001 40MHz Crystal Oscillator"</span>,
    <span class="k">"permalink"</span>:  <span class="s">"/product/cm4040m00012001-40mhz-crystal-oscillator"</span>,
    <span class="k">"sku"</span>:        <span class="s">"CM4040M00012001"</span>,
    <span class="k">"updated_at"</span>: <span class="s">"2026-04-28T10:22:00"</span>
  }
}</pre>
      </div>
    </div>
  </section>

  <!-- ─────────────────────── CATEGORIES ─────────────────────── -->

  <section id="all-categories">
    <h2>Categories</h2>

    <div class="endpoint">
      <div class="endpoint-head">
        <span class="method badge-get">GET</span>
        <span class="path">/api/get/categories</span>
        <span class="auth-badge">🔑 auth required</span>
      </div>
      <div class="endpoint-body">
        <p>Returns a flat list of product categories. Use <code>parent</code> to get direct children of a specific category. <code>permalink</code> is a slash-joined slug path from root to that category.</p>

        <h3>Query parameters</h3>
        <table>
          <thead><tr><th>Param</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
          <tbody>
            <tr><td><code>mode</code></td><td>string</td><td><code>full</code></td><td><code>minimal</code> · <code>summary</code> · <code>full</code></td></tr>
            <tr><td><code>page</code></td><td>integer · <code>"all"</code></td><td><code>1</code></td><td>Pass <code>all</code> to return every category</td></tr>
            <tr><td><code>per_page</code></td><td>integer</td><td><code>20</code></td><td>Results per page</td></tr>
            <tr><td><code>parent</code></td><td>string</td><td>—</td><td>Slug of parent category. Returns only direct children</td></tr>
          </tbody>
        </table>

        <h3>Response — <code>mode=full</code></h3>
        <pre>{
  <span class="k">"mode"</span>:    <span class="s">"full"</span>,
  <span class="k">"page"</span>:    <span class="n">1</span>,
  <span class="k">"perPage"</span>: <span class="n">20</span>,
  <span class="k">"total"</span>:   <span class="n">48</span>,
  <span class="k">"categories"</span>: [
    {
      <span class="k">"id"</span>:             <span class="n">12</span>,
      <span class="k">"name"</span>:           <span class="s">"Crystal Oscillators"</span>,
      <span class="k">"slug"</span>:           <span class="s">"crystal-oscillators"</span>,
      <span class="k">"description"</span>:   <span class="s">"SMD and through-hole crystal oscillators"</span>,
      <span class="k">"depth"</span>:          <span class="n">2</span>,
      <span class="k">"parent_id"</span>:      <span class="n">5</span>,
      <span class="k">"permalink"</span>:      <span class="s">"electronic-components/passive/crystal-oscillators"</span>,
      <span class="k">"total_products"</span>: <span class="n">34</span>,
      <span class="k">"updated_at"</span>:     <span class="s">"2026-03-12T09:00:00"</span>
    }
  ]
}</pre>

        <h3>Examples</h3>
        <pre><span class="c">// All categories flat list</span>
GET /api/get/categories?mode=minimal&page=all

<span class="c">// Direct children of a parent</span>
GET /api/get/categories?parent=electronic-components</pre>
      </div>
    </div>
  </section>

  <!-- ── Single category ── -->
  <section id="single-category">
    <div class="endpoint">
      <div class="endpoint-head">
        <span class="method badge-get">GET</span>
        <span class="path">/api/get/category/<strong>{slug}</strong></span>
        <span class="auth-badge">🔑 auth required</span>
      </div>
      <div class="endpoint-body">
        <p>Returns a single category plus its products. Use <code>mode</code> to control the category detail level and <code>pMode</code> for the product list detail level.</p>

        <h3>Query parameters</h3>
        <table>
          <thead><tr><th>Param</th><th>Type</th><th>Default</th><th>Description</th></tr></thead>
          <tbody>
            <tr><td><code>mode</code></td><td>string</td><td><code>minimal</code></td><td>Category detail level: <code>minimal</code> · <code>summary</code> · <code>full</code></td></tr>
            <tr><td><code>pMode</code></td><td>string</td><td><code>summary</code></td><td>Product list detail level: <code>minimal</code> · <code>summary</code> · <code>full</code></td></tr>
            <tr><td><code>page</code></td><td>integer</td><td><code>1</code></td><td>Product list page</td></tr>
            <tr><td><code>per_page</code></td><td>integer</td><td><code>20</code></td><td>Products per page</td></tr>
          </tbody>
        </table>

        <h3>Response</h3>
        <pre>{
  <span class="k">"page"</span>:    <span class="n">1</span>,
  <span class="k">"perPage"</span>: <span class="n">20</span>,
  <span class="k">"total"</span>:   <span class="n">34</span>,

  <span class="k">"category"</span>: {
    <span class="k">"id"</span>:          <span class="n">12</span>,
    <span class="k">"title"</span>:       <span class="s">"Crystal Oscillators"</span>,
    <span class="k">"permalink"</span>:   <span class="s">"electronic-components/passive/crystal-oscillators"</span>,
    <span class="k">"description"</span>: <span class="s">"SMD and through-hole crystal oscillators"</span>,
    <span class="k">"parent_id"</span>:   <span class="n">5</span>,
    <span class="k">"breadcrumb"</span>: [
      { <span class="k">"name"</span>: <span class="s">"Electronic Components"</span>, <span class="k">"slug"</span>: <span class="s">"electronic-components"</span> },
      { <span class="k">"name"</span>: <span class="s">"Passive Components"</span>,    <span class="k">"slug"</span>: <span class="s">"passive-components"</span> },
      { <span class="k">"name"</span>: <span class="s">"Crystal Oscillators"</span>,   <span class="k">"slug"</span>: <span class="s">"crystal-oscillators"</span> }
    ]
  },

  <span class="k">"products"</span>: {
    <span class="k">"total"</span>: <span class="n">34</span>,
    <span class="k">"data"</span>: [ <span class="c">/* product objects at pMode depth — omitted in mode=minimal */</span> ]
  }
}</pre>

        <div class="note">
          <strong>Note — products.data</strong>
          The <code>products.data</code> array is only present when <code>mode</code> is <code>summary</code> or <code>full</code>. In <code>mode=minimal</code> only <code>products.total</code> is returned — useful for rendering category cards without loading all products.
        </div>

        <h3>Examples</h3>
        <pre><span class="c">// Category page — full category info + summary product cards</span>
GET /api/get/category/crystal-oscillators?mode=full&pMode=summary&page=1&per_page=24

<span class="c">// Just the product count for a category card</span>
GET /api/get/category/crystal-oscillators?mode=minimal</pre>
      </div>
    </div>
  </section>

</main>
</body>
</html>
