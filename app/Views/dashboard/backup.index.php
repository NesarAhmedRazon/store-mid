<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>  
    <title>Dashboard — SMDPicker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style type="text/tailwindcss">
      @theme {
        --color-clifford: #da373d;
      }
    </style>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:        #F4F3EF;
            --surface:   #FAFAF8;
            --border:    #E2E0D9;
            --border-md: #C8C6BC;
            --text:      #1A1917;
            --muted:     #78766E;
            --subtle:    #A8A69E;
            --up:        #1A6B3C;
            --up-bg:     #E6F4EC;
            --down:      #9B2020;
            --down-bg:   #FAEAEA;
            --warn:      #7A4E00;
            --warn-bg:   #FEF3DC;
            --info:      #0F4C8A;
            --info-bg:   #E8F0FB;
            --accent:    #1A1917;
            --mono:      'IBM Plex Mono', monospace;
            --sans:      'IBM Plex Sans', sans-serif;
        }

        body {
            font-family: var(--sans);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Layout */
        .shell { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            width: 220px;
            flex-shrink: 0;
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 0;
            position: fixed;
            top: 0; left: 0; bottom: 0;
        }

        .sidebar-brand {
            padding: 20px 20px 18px;
            border-bottom: 1px solid var(--border);
        }
        .brand-name {
            font-family: var(--mono);
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.04em;
            color: var(--text);
        }
        .brand-tag {
            font-size: 10px;
            color: var(--subtle);
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 2px;
        }

        .nav { padding: 12px 10px; flex: 1; }
        .nav-group-label {
            font-size: 10px;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--subtle);
            padding: 8px 10px 4px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 7px 10px;
            border-radius: 6px;
            font-size: 13px;
            color: var(--muted);
            cursor: pointer;
            text-decoration: none;
            transition: background 0.15s, color 0.15s;
            margin-bottom: 1px;
        }
        .nav-item:hover { background: var(--bg); color: var(--text); }
        .nav-item.active { background: var(--text); color: #fff; }
        .nav-item svg { flex-shrink: 0; opacity: 0.7; }
        .nav-item.active svg { opacity: 1; }

        .sidebar-footer {
            padding: 14px 20px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .avatar {
            width: 30px; height: 30px;
            border-radius: 50%;
            background: var(--text);
            color: #fff;
            font-size: 11px;
            font-weight: 500;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
            font-family: var(--mono);
        }
        .footer-info { flex: 1; min-width: 0; }
        .footer-name { font-size: 12px; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .footer-role { font-size: 10px; color: var(--subtle); text-transform: uppercase; letter-spacing: 0.06em; }
        .logout-btn {
            font-size: 11px;
            color: var(--subtle);
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid var(--border);
            transition: all 0.15s;
            white-space: nowrap;
        }
        .logout-btn:hover { border-color: var(--border-md); color: var(--muted); }

        /* Main */
        .main { margin-left: 220px; flex: 1; display: flex; flex-direction: column; }

        /* Topbar */
        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 28px;
            height: 54px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .topbar-left h1 { font-size: 14px; font-weight: 500; color: var(--text); }
        .topbar-left span { font-size: 12px; color: var(--subtle); margin-left: 10px; font-family: var(--mono); }
        .topbar-right { display: flex; align-items: center; gap: 10px; }

        .sys-status {
            display: flex; align-items: center; gap: 6px;
            font-size: 11px; color: var(--up);
            background: var(--up-bg);
            padding: 4px 10px;
            border-radius: 20px;
            font-family: var(--mono);
        }
        .pulse {
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--up);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .refresh-btn {
            display: flex; align-items: center; gap: 6px;
            font-size: 12px; font-family: var(--mono);
            color: var(--muted);
            background: none;
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 5px 12px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .refresh-btn:hover { border-color: var(--border-md); color: var(--text); }

        /* Content */
        .content { padding: 28px; }

        /* Metrics */
        .metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .metric {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px 18px;
            position: relative;
            overflow: hidden;
        }
        .metric::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 2px;
            background: var(--border-md);
        }
        .metric.up::before { background: var(--up); }
        .metric.down::before { background: var(--down); }
        .metric.warn::before { background: #D4850A; }
        .metric.info::before { background: var(--info); }

        .metric-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--subtle); margin-bottom: 8px; }
        .metric-value { font-size: 28px; font-weight: 300; color: var(--text); font-family: var(--mono); letter-spacing: -0.02em; }
        .metric-value span { font-size: 13px; font-weight: 400; color: var(--muted); margin-left: 4px; }
        .metric-footer { font-size: 11px; color: var(--subtle); margin-top: 6px; }

        /* Grid */
        .grid-1 { display: grid; grid-template-columns: minmax(0, 1fr); gap: 16px; margin-bottom: 16px; }
        .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; margin-bottom: 16px; }
        /* Column Widths (Percentage-based) */
        .col-1 { grid-column: span 1; }
        .col-2 { grid-column: span 2; }
        .col-3 { grid-column: span 3; }
        .col-4 { grid-column: span 4; }
        .col-5 { grid-column: span 5; }
        .col-6 { grid-column: span 6; }
        /* Cards */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
        }
        .card-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 18px;
            border-bottom: 1px solid var(--border);
        }
        .card-title { font-size: 12px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.08em; color: var(--muted); }
        .card-action { font-size: 11px; color: var(--subtle); font-family: var(--mono); cursor: pointer; }
        .card-action:hover { color: var(--muted); }
        .card-body { padding: 0; }

        /* Badge */
        .badge {
            font-size: 10px; font-family: var(--mono);
            padding: 2px 8px; border-radius: 3px;
            font-weight: 500; letter-spacing: 0.04em;
        }
        .badge-up    { background: var(--up-bg);   color: var(--up);   }
        .badge-down  { background: var(--down-bg);  color: var(--down); }
        .badge-warn  { background: var(--warn-bg);  color: var(--warn); }
        .badge-info  { background: var(--info-bg);  color: var(--info); }

        /* Endpoint rows */
        .ep-row {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 18px;
            border-bottom: 1px solid var(--border);
            transition: background 0.1s;
        }
        .ep-row:last-child { border-bottom: none; }
        .ep-row:hover { background: var(--bg); }
        .ep-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
        .ep-dot.up   { background: var(--up); }
        .ep-dot.down { background: var(--down); }
        .ep-dot.warn { background: #D4850A; }
        .ep-name { font-family: var(--mono); font-size: 12px; color: var(--text); flex: 1; }
        .ep-method { font-family: var(--mono); font-size: 10px; color: var(--subtle); width: 34px; }
        .ep-latency { font-family: var(--mono); font-size: 11px; color: var(--subtle); min-width: 52px; text-align: right; }

        /* Hook rows */
        .hook-row {
            display: flex; align-items: center; gap: 12px;
            padding: 11px 18px;
            border-bottom: 1px solid var(--border);
            transition: background 0.1s;
        }
        .hook-row:last-child { border-bottom: none; }
        .hook-row:hover { background: var(--bg); }
        .hook-icon {
            width: 28px; height: 28px; border-radius: 5px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; background: var(--bg); border: 1px solid var(--border);
        }
        .hook-name { font-family: var(--mono); font-size: 12px; color: var(--text); flex: 1; }
        .hook-time { font-size: 11px; color: var(--subtle); min-width: 60px; text-align: right; }

        /* Log table */
        .log-table { width: 100%; border-collapse: collapse; }
        .log-table th {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em;
            color: var(--subtle); font-weight: 500;
            padding: 10px 18px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            background: var(--bg);
        }
        .log-table td {
            padding: 10px 18px;
            font-size: 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        .log-table tr:last-child td { border-bottom: none; }
        .log-table tr:hover td { background: var(--bg); }
        .log-table td:first-child { width: 52px; }

        .method-badge {
            font-family: var(--mono); font-size: 10px;
            padding: 2px 6px; border-radius: 3px; font-weight: 500;
            display: inline-block;
        }
        .method-get  { background: var(--up-bg);   color: var(--up); }
        .method-post { background: var(--info-bg);  color: var(--info); }
        .method-put  { background: var(--warn-bg);  color: var(--warn); }
        .method-del  { background: var(--down-bg);  color: var(--down); }

        .log-path { font-family: var(--mono); font-size: 12px; color: var(--text); }
        .log-path .qs { color: var(--subtle); }
        .status-200 { color: var(--up); font-family: var(--mono); }
        .status-4xx { color: var(--warn); font-family: var(--mono); }
        .status-5xx { color: var(--down); font-family: var(--mono); }
        .log-time { color: var(--subtle); font-family: var(--mono); font-size: 11px; white-space: nowrap; }
        .log-source { color: var(--subtle); font-size: 11px; }

        /* Product table */
        .prod-table { width: 100%; border-collapse: collapse; table-layout: auto; }
        .prod-table th {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em;
            color: var(--subtle); font-weight: 500;
            padding: 10px 18px; text-align: left;
            border-bottom: 1px solid var(--border);
            background: var(--bg);
            white-space: nowrap;
        }
        .prod-table td {
            padding: 10px 18px; font-size: 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
            white-space: nowrap;
        }
        .prod-table tr:last-child td { border-bottom: none; }
        .prod-table tr:hover td { background: var(--bg); }
        .prod-table .col-title { width: 100%; white-space: normal; }
        .prod-table .col-stock { text-align: right; }
        .prod-table .col-price { text-align: right; }
        .prod-sku   { font-family: var(--mono); color: var(--muted); }
        .prod-id    { font-family: var(--mono); color: var(--subtle); font-size: 11px; }
        .prod-title { color: var(--text); font-weight: 500; }
        .prod-stock { font-family: var(--mono); color: var(--text); }
        .prod-price { font-family: var(--mono); color: var(--text); }
        .prod-price .sale { color: var(--up); }
        .prod-price .regular { color: var(--subtle); font-size: 11px; text-decoration: line-through; margin-left: 4px; }
        .prod-updated { font-family: var(--mono); font-size: 11px; color: var(--subtle); }

        /* Placeholder notice */
        .placeholder-note {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 10px; color: var(--subtle); font-family: var(--mono);
            background: var(--bg);
            border: 1px dashed var(--border-md);
            padding: 2px 8px; border-radius: 3px;
        }
    </style>
</head>
<body>

<div class="shell">

    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-name">SMDPicker</div>
            <div class="brand-tag">Middleware Monitor</div>
        </div>

        <nav class="nav">
            <div class="nav-group-label">Monitor</div>
            <a href="/dashboard" class="nav-item active">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><rect x="1" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="1" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="1" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="9" y="9" width="6" height="6" rx="1" stroke="currentColor" stroke-width="1.4"/></svg>
                Dashboard
            </a>
            <a href="#" class="nav-item">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/><path d="M8 4v4l3 2" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                Request log
            </a>
            <a href="#" class="nav-item">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M2 11l4-4 3 3 5-6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Endpoints
            </a>
            <a href="#" class="nav-item">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><path d="M14 3H2v2h12V3zM14 7H2v2h12V7zM8 11H2v2h6v-2z" fill="currentColor" opacity=".6"/></svg>
                Hooks
            </a>
            <a href="#" class="nav-item">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><rect x="1.5" y="1.5" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="9.5" y="1.5" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.4"/><rect x="1.5" y="9.5" width="5" height="5" rx="1" stroke="currentColor" stroke-width="1.4"/><path d="M9.5 12h5M12 9.5v5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                Products
            </a>

            <?php if (session()->get('role') === 'admin'): ?>
            <div class="nav-group-label" style="margin-top:12px;">Admin</div>
            <a href="#" class="nav-item">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none"><circle cx="6" cy="5" r="3" stroke="currentColor" stroke-width="1.4"/><path d="M1 13c0-2.761 2.239-5 5-5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/><path d="M11 9v6M8 12h6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                Users
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <div class="avatar"><?= strtoupper(substr(session()->get('name') ?? 'U', 0, 2)) ?></div>
            <div class="footer-info">
                <div class="footer-name"><?= esc(session()->get('name')) ?></div>
                <div class="footer-role"><?= esc(session()->get('role')) ?></div>
            </div>
            <a href="/logout" class="logout-btn">out</a>
        </div>
    </aside>

    <!-- Main -->
    <div class="main">

        <!-- Topbar -->
        <header class="topbar">
            <div class="topbar-left">
                <h1>Dashboard <span><?= date('D, d M Y') ?></span></h1>
            </div>
            <div class="topbar-right">
                <div class="sys-status">
                    <div class="pulse"></div>
                    system nominal
                </div>
                <button class="refresh-btn" onclick="window.location.reload()">
                    <svg width="12" height="12" viewBox="0 0 16 16" fill="none"><path d="M13.5 8A5.5 5.5 0 1 1 8 2.5c1.8 0 3.4.87 4.4 2.2L14 3v4h-4l1.6-1.6A4 4 0 1 0 12 8" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                    refresh
                </button>
            </div>
        </header>

        <!-- Content -->
        <div class="content">

            <!-- Metric cards -->
            <div class="metrics">
                <div class="metric info">
                    <div class="metric-label">Total endpoints</div>
                    <div class="metric-value">—</div>
                    <div class="metric-footer">registered in system</div>
                </div>
                <div class="metric up">
                    <div class="metric-label">Endpoints up</div>
                    <div class="metric-value">—</div>
                    <div class="metric-footer">last checked now</div>
                </div>
                <div class="metric warn">
                    <div class="metric-label">Hooks today</div>
                    <div class="metric-value">—</div>
                    <div class="metric-footer">fired since midnight</div>
                </div>
                <div class="metric down">
                    <div class="metric-label">Errors (24h)</div>
                    <div class="metric-value">—</div>
                    <div class="metric-footer">4xx + 5xx combined</div>
                </div>
            </div>

            <!-- Endpoint status + Hook activity -->
            <div class="grid-2">
                <!-- Products -->
                <div class="card col-2" >
                    <div class="card-head">
                        <span class="card-title">Recent products</span>
                        <span class="placeholder-note">placeholder data</span>
                    </div>
                    <div class="card-body">
                        <table class="prod-table">
                            <thead>
                                <tr>
                                    <th class="col-id">WC ID</th>
                                    <th class="col-sku">SKU</th>
                                    <th class="col-title">Title</th>
                                    <th class="col-stock">Stock</th>
                                    <th class="col-status">Status</th>
                                    <th class="col-price">Price</th>
                                    <th class="col-updated">Last updated</th>
                                </tr>
                            </thead>
                            <tbody>
                            
                                <?php foreach ($products as $p): 
                                    
                                    log_message('debug',print_r($p, true));

                                    ?>
                                <tr>
                                    <td><span class="prod-id">#<?= $p->id ?></span></td>
                                    <td><span class="prod-sku"><?= esc($p->sku) ?></span></td>
                                    <td class="col-title"><span class="prod-title"><?= esc($p->title) ?></span></td>
                                    <td class="col-stock"><span class="prod-stock"><?= number_format($p->stock_quantity) ?></span></td>
                                    <td><span class="badge badge-<?= $p->stock_status === 'instock' ? 'up' : ($p->stock_status === 'onbackorder' ? 'warn' : 'down') ?>"><?= $p->stock_status ?></span></td>
                                    <td class="col-price">
                                        <span class="prod-price">
                                            <span class="sale">৳<?= number_format($p->price->offer ?? $p->price->regular, 2) ?></span>
                                            <?php if ($p->price->offer): ?><span class="regular">৳<?= number_format($p->price->regular, 2) ?></span><?php endif; ?>
                                        </span>
                                    </td>
                                    <td><span class="prod-updated"><?= $p->updated_at ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                

                <div class="card col-1">
                    <div class="card-head">
                        <span class="card-title">Recent hook activity</span>
                        <span class="placeholder-note">placeholder data</span>
                    </div>
                    <div class="card-body">
                        <div class="hook-row">
                            <div class="hook-icon">
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="none"><path d="M2 8l4 4 8-8" stroke="var(--up)" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </div>
                            <span class="hook-name">order.created</span>
                            <span class="badge badge-up">200</span>
                            <span class="hook-time">2 min ago</span>
                        </div>
                        <div class="hook-row">
                            <div class="hook-icon">
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="none"><path d="M2 8l4 4 8-8" stroke="var(--up)" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </div>
                            <span class="hook-name">stock.updated</span>
                            <span class="badge badge-up">200</span>
                            <span class="hook-time">15 min ago</span>
                        </div>
                        <div class="hook-row">
                            <div class="hook-icon">
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="none"><path d="M4 4l8 8M12 4l-8 8" stroke="var(--down)" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </div>
                            <span class="hook-name">payment.confirmed</span>
                            <span class="badge badge-down">500</span>
                            <span class="hook-time">1 hr ago</span>
                        </div>
                        <div class="hook-row">
                            <div class="hook-icon">
                                <svg width="12" height="12" viewBox="0 0 16 16" fill="none"><path d="M2 8l4 4 8-8" stroke="var(--up)" stroke-width="1.8" stroke-linecap="round"/></svg>
                            </div>
                            <span class="hook-name">order.shipped</span>
                            <span class="badge badge-up">200</span>
                            <span class="hook-time">3 hr ago</span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Request log -->
            <div class="card">
                <div class="card-head">
                    <span class="card-title">Request log</span>
                    <span class="placeholder-note">placeholder data</span>
                </div>
                <div class="card-body">
                    <table class="log-table">
                        <thead>
                            <tr>
                                <th>Method</th>
                                <th>Path</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="method-badge method-get">GET</span></td>
                                <td><span class="log-path">/api/v1/orders<span class="qs">?limit=20</span></span></td>
                                <td><span class="status-200">200</span></td>
                                <td><span class="log-source">192.168.1.10</span></td>
                                <td><span class="log-time">just now</span></td>
                            </tr>
                            <tr>
                                <td><span class="method-badge method-post">POST</span></td>
                                <td><span class="log-path">/api/v1/sync</span></td>
                                <td><span class="status-200">201</span></td>
                                <td><span class="log-source">10.0.0.4</span></td>
                                <td><span class="log-time">2 min ago</span></td>
                            </tr>
                            <tr>
                                <td><span class="method-badge method-post">POST</span></td>
                                <td><span class="log-path">/api/v1/webhook/payment</span></td>
                                <td><span class="status-5xx">500</span></td>
                                <td><span class="log-source">stripe.com</span></td>
                                <td><span class="log-time">1 hr ago</span></td>
                            </tr>
                            <tr>
                                <td><span class="method-badge method-get">GET</span></td>
                                <td><span class="log-path">/api/v1/products<span class="qs">?category=smd</span></span></td>
                                <td><span class="status-200">200</span></td>
                                <td><span class="log-source">192.168.1.22</span></td>
                                <td><span class="log-time">1 hr ago</span></td>
                            </tr>
                            <tr>
                                <td><span class="method-badge method-get">GET</span></td>
                                <td><span class="log-path">/api/v1/orders/9982</span></td>
                                <td><span class="status-4xx">404</span></td>
                                <td><span class="log-source">10.0.0.7</span></td>
                                <td><span class="log-time">2 hr ago</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card" style="margin-top: 16px;">
                    <div class="card-head">
                        <span class="card-title">API endpoint status</span>
                        <span class="placeholder-note">placeholder data</span>
                    </div>
                    <div class="card-body">
                        <div class="ep-row">
                            <div class="ep-dot up"></div>
                            <span class="ep-method">GET</span>
                            <span class="ep-name">/api/v1/orders</span>
                            <span class="badge badge-up">up</span>
                            <span class="ep-latency">~120ms</span>
                        </div>
                        <div class="ep-row">
                            <div class="ep-dot up"></div>
                            <span class="ep-method">GET</span>
                            <span class="ep-name">/api/v1/products</span>
                            <span class="badge badge-up">up</span>
                            <span class="ep-latency">~98ms</span>
                        </div>
                        <div class="ep-row">
                            <div class="ep-dot warn"></div>
                            <span class="ep-method">POST</span>
                            <span class="ep-name">/api/v1/sync</span>
                            <span class="badge badge-warn">slow</span>
                            <span class="ep-latency">~340ms</span>
                        </div>
                        <div class="ep-row">
                            <div class="ep-dot down"></div>
                            <span class="ep-method">POST</span>
                            <span class="ep-name">/api/v1/webhook</span>
                            <span class="badge badge-down">down</span>
                            <span class="ep-latency">timeout</span>
                        </div>
                    </div>
                </div>
            

        </div><!-- /content -->
    </div><!-- /main -->
</div><!-- /shell -->

</body>
</html>
