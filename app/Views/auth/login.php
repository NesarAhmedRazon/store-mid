<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SMDPicker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500&display=swap" rel="stylesheet">
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
            --down:      #9B2020;
            --down-bg:   #FAEAEA;
            --mono:      'IBM Plex Mono', monospace;
            --sans:      'IBM Plex Sans', sans-serif;
        }

        body {
            font-family: var(--sans);
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            line-height: 1.5;
        }

        /* Subtle grid background */
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(var(--border) 1px, transparent 1px),
                linear-gradient(90deg, var(--border) 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.5;
            pointer-events: none;
        }

        .wrap {
            position: relative;
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        /* Brand header */
        .brand {
            text-align: center;
            margin-bottom: 28px;
        }
        .brand-name {
            font-family: var(--mono);
            font-size: 18px;
            font-weight: 500;
            letter-spacing: 0.04em;
            color: var(--text);
        }
        .brand-tag {
            font-size: 11px;
            color: var(--subtle);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            margin-top: 4px;
        }

        /* Card */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
        }

        .card-head {
            padding: 18px 24px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-title {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }
        .sys-status {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 10px;
            color: #1A6B3C;
            font-family: var(--mono);
        }
        .pulse {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #1A6B3C;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        .card-body { padding: 24px; }

        /* Error alert */
        .alert-error {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--down-bg);
            border: 1px solid #F0BFBF;
            border-radius: 6px;
            padding: 11px 14px;
            margin-bottom: 20px;
            font-size: 13px;
            color: var(--down);
        }
        .alert-error svg { flex-shrink: 0; margin-top: 1px; }

        /* Form */
        .field { margin-bottom: 16px; }
        .field label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
            margin-bottom: 6px;
        }
        .field input {
            width: 100%;
            padding: 9px 12px;
            font-family: var(--mono);
            font-size: 13px;
            color: var(--text);
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 6px;
            outline: none;
            transition: border-color 0.15s, background 0.15s;
        }
        .field input::placeholder { color: var(--subtle); }
        .field input:focus {
            border-color: var(--border-md);
            background: var(--surface);
        }
        .field input:hover:not(:focus) { border-color: var(--border-md); }

        /* Submit */
        .btn-submit {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            font-family: var(--mono);
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.04em;
            color: #fff;
            background: var(--text);
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.1s;
        }
        .btn-submit:hover   { opacity: 0.85; }
        .btn-submit:active  { transform: scale(0.99); }

        /* Footer */
        .card-foot {
            padding: 12px 24px;
            border-top: 1px solid var(--border);
            background: var(--bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .foot-note {
            font-size: 11px;
            color: var(--subtle);
            font-family: var(--mono);
        }
        .foot-version {
            font-size: 10px;
            color: var(--subtle);
            font-family: var(--mono);
            letter-spacing: 0.06em;
        }

        /* Below card */
        .below-card {
            text-align: center;
            margin-top: 16px;
            font-size: 11px;
            color: var(--subtle);
            font-family: var(--mono);
        }
    </style>
</head>
<body>

<div class="wrap">

    <div class="brand">
        <div class="brand-name">SMDPicker</div>
        <div class="brand-tag">Middleware Monitor</div>
    </div>

    <div class="card">

        <div class="card-head">
            <span class="card-title">Sign in</span>
            <div class="sys-status">
                <div class="pulse"></div>
                system online
            </div>
        </div>

        <div class="card-body">

            <?php if (session()->getFlashdata('error')): ?>
            <div class="alert-error">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none">
                    <circle cx="8" cy="8" r="6.5" stroke="#9B2020" stroke-width="1.4"/>
                    <path d="M8 5v3.5M8 11h.01" stroke="#9B2020" stroke-width="1.4" stroke-linecap="round"/>
                </svg>
                <?= esc(session()->getFlashdata('error')) ?>
            </div>
            <?php endif; 

                $val = set_value('email');
                if (ENVIRONMENT === 'development'):
                    $val = env('ADMIN_EMAIL','');
                endif;
                  
            ?>

            <?= form_open('/login') ?>

                <div class="field">
                    <label for="email">Email address</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?= $val ?>"
                        placeholder="you@example.com"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="btn-submit">sign in →</button>

            <?= form_close() ?>

        </div>

        <div class="card-foot">
            <span class="foot-note"><?= date('D, d M Y') ?></span>
            <span class="foot-version">v1.0.0</span>
        </div>

    </div>

    <div class="below-card">
        access restricted to authorised users
    </div>

</div>

</body>
</html>
