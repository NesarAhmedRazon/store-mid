<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'SMDPicker' ?> — SMDPicker</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <style type="text/tailwindcss">
        @theme {
            --font-sans:  'IBM Plex Sans', sans-serif;
            --font-mono:  'IBM Plex Mono', monospace;

            --color-bg:         #F4F3EF;
            --color-surface:    #FAFAF8;
            --color-border:     #E2E0D9;
            --color-border-md:  #C8C6BC;
            --color-text:       #1A1917;
            --color-muted:      #78766E;
            --color-subtle:     #A8A69E;

            --color-up:         #1A6B3C;
            --color-up-bg:      #E6F4EC;
            --color-down:       #9B2020;
            --color-down-bg:    #FAEAEA;
            --color-warn:       #7A4E00;
            --color-warn-bg:    #FEF3DC;
            --color-info:       #0F4C8A;
            --color-info-bg:    #E8F0FB;
        }

        @layer base {
            body {
                @apply font-sans bg-bg text-text text-sm leading-relaxed;
            }
        }

        @layer components {
            /* Badges */
            .badge {
                @apply inline-block font-mono text-[10px] font-medium tracking-wide px-2 py-0.5 rounded-sm;
            }
            .badge-up   { @apply bg-up-bg text-up; }
            .badge-down { @apply bg-down-bg text-down; }
            .badge-warn { @apply bg-warn-bg text-warn; }
            .badge-info { @apply bg-info-bg text-info; }

            /* Cards */
            .card {
                @apply bg-surface border border-border rounded-lg overflow-hidden;
            }
            .card-head {
                @apply flex items-center justify-between px-4 py-3 border-b border-border;
            }
            .card-title {
                @apply text-[11px] font-medium uppercase tracking-widest text-muted;
            }

            /* Placeholder tag */
            .placeholder-note {
                @apply inline-flex items-center gap-1 text-[10px] text-subtle font-mono
                       bg-bg border border-dashed border-border-md px-2 py-0.5 rounded-sm;
            }

            /* Nav item */
            .nav-item {
                @apply flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[13px]
                       text-muted no-underline transition-colors duration-150 mb-px;
            }
            .nav-item:hover  { @apply bg-bg text-text; }
            .nav-item.active { @apply bg-text text-white; }

            /* Metric card */
            .metric {
                @apply bg-surface border border-border rounded-lg p-4 relative overflow-hidden;
            }
            .metric::before {
                content: '';
                @apply absolute top-0 left-0 right-0 h-0.5 bg-border-md;
            }
            .metric-up::before    { @apply bg-up; }
            .metric-down::before  { @apply bg-down; }
            .metric-warn::before  { background: #D4850A; }
            .metric-info::before  { @apply bg-info; }
        }
    </style>
</head>