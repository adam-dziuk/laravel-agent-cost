<style>
    :root {
        color-scheme: light;
        --page: #f9f9f7;
        --surface: #fcfcfb;
        --ink: #0b0b0b;
        --ink-secondary: #52514e;
        --ink-muted: #898781;
        --grid: #e1e0d9;
        --axis: #c3c2b7;
        --border: rgba(11, 11, 11, 0.1);
        --wash: rgba(11, 11, 11, 0.04);
        --series: #2a78d6;
        --series-wash: rgba(42, 120, 214, 0.1);
    }

    @media (prefers-color-scheme: dark) {
        :root {
            color-scheme: dark;
            --page: #0d0d0d;
            --surface: #1a1a19;
            --ink: #ffffff;
            --ink-secondary: #c3c2b7;
            --ink-muted: #898781;
            --grid: #2c2c2a;
            --axis: #383835;
            --border: rgba(255, 255, 255, 0.1);
            --wash: rgba(255, 255, 255, 0.05);
            --series: #3987e5;
            --series-wash: rgba(57, 135, 229, 0.12);
        }
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
        margin: 0;
        background: var(--page);
        color: var(--ink);
        font: 14px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    h1, h2, p, dl, dd { margin: 0; }

    code { font: 0.92em ui-monospace, SFMono-Regular, Menlo, monospace; }

    .page {
        display: grid;
        gap: 16px;
        max-width: 1120px;
        margin: 0 auto;
        padding: 40px 16px 64px;
    }

    h1 { font-size: 22px; font-weight: 600; letter-spacing: -0.01em; }

    .subtle { color: var(--ink-secondary); }

    /* Range filter */

    .ranges {
        display: flex;
        flex-wrap: wrap;
        gap: 2px;
        width: fit-content;
        padding: 2px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
    }

    .ranges a {
        padding: 4px 12px;
        border-radius: 6px;
        color: var(--ink-secondary);
        font-size: 13px;
        text-decoration: none;
    }

    .ranges a:hover { background: var(--wash); color: var(--ink); }
    .ranges a[aria-current] { background: var(--ink); color: var(--page); }
    .ranges a:focus-visible { outline: 2px solid var(--series); outline-offset: 1px; }

    .notice {
        padding: 10px 14px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        color: var(--ink-secondary);
    }

    /* Summary figures */

    .stats {
        display: flex;
        flex-wrap: wrap;
        align-items: flex-end;
        gap: 16px 48px;
        padding: 8px 0;
    }

    .stats dt { color: var(--ink-secondary); font-size: 13px; }
    .stats dd { font-size: 24px; font-weight: 600; letter-spacing: -0.01em; line-height: 1.2; }
    .stats .stat-hero dd { font-size: 48px; letter-spacing: -0.02em; line-height: 1.05; }

    .card {
        min-width: 0;
        padding: 20px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 12px;
    }

    .card h2 { margin-bottom: 16px; font-size: 15px; font-weight: 600; }

    .empty h2 { margin-bottom: 4px; }
    .empty p { max-width: 70ch; color: var(--ink-secondary); }

    /* Column chart */

    .chart {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        column-gap: 8px;
        border-radius: 6px;
    }

    .chart:focus { outline: none; }
    .chart:focus-visible { outline: 2px solid var(--series); outline-offset: 6px; }

    .y-axis, .plot { position: relative; height: 200px; }

    .y-axis {
        color: var(--ink-muted);
        font-size: 11px;
        font-variant-numeric: tabular-nums;
    }

    .y-axis span {
        position: absolute;
        right: 0;
        line-height: 1;
        white-space: nowrap;
        transform: translateY(50%);
    }

    .plot { border-bottom: 1px solid var(--axis); }

    .gridline {
        position: absolute;
        left: 0;
        right: 0;
        border-top: 1px solid var(--grid);
    }

    .columns { position: absolute; inset: 0; display: flex; }

    .column {
        display: flex;
        flex: 1 1 0;
        align-items: flex-end;
        justify-content: center;
        min-width: 0;
        padding: 0 1px;
    }

    .column.is-active { background: var(--wash); }

    .bar {
        width: 100%;
        max-width: 24px;
        background: var(--series);
        border-radius: 4px 4px 0 0;
    }

    .column.is-active .bar { filter: brightness(1.15); }

    .x-axis {
        display: flex;
        grid-column: 2;
        padding-top: 8px;
        color: var(--ink-muted);
        font-size: 11px;
        font-variant-numeric: tabular-nums;
    }

    .x-axis span {
        display: flex;
        flex: 1 1 0;
        justify-content: center;
        min-width: 0;
        min-height: 1em;
        line-height: 1;
        white-space: nowrap;
    }

    @media (max-width: 600px) {
        .x-axis .minor { visibility: hidden; }
    }

    .tooltip {
        position: fixed;
        z-index: 10;
        padding: 6px 10px;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.12);
        font-size: 12px;
        white-space: nowrap;
        pointer-events: none;
        transform: translate(-50%, calc(-100% - 8px));
    }

    .tooltip[hidden] { display: none; }
    .tooltip strong { display: block; font-size: 14px; font-weight: 600; font-variant-numeric: tabular-nums; }
    .tooltip span { color: var(--ink-secondary); }

    /* Tables */

    .table-scroll { overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; }

    th, td {
        padding: 10px 12px;
        text-align: left;
        vertical-align: middle;
        border-bottom: 1px solid var(--grid);
    }

    th:first-child, td:first-child { padding-left: 0; }
    th:last-child, td:last-child { padding-right: 0; }
    tbody tr:last-child > * { border-bottom: 0; }

    thead th { color: var(--ink-secondary); font-size: 12px; font-weight: 500; white-space: nowrap; }
    tbody th { font-weight: 400; white-space: nowrap; }

    .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }

    .agents td:first-child { min-width: 220px; }
    .agent-name { display: block; font-weight: 600; }

    .agent-class {
        display: block;
        color: var(--ink-secondary);
        font: 12px ui-monospace, SFMono-Regular, Menlo, monospace;
        overflow-wrap: break-word;
    }

    .sparkline { display: block; overflow: visible; }
    .sparkline-area { fill: var(--series-wash); }

    .sparkline-line {
        fill: none;
        stroke: var(--series);
        stroke-width: 2;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    .sparkline-dot { fill: var(--series); stroke: var(--surface); stroke-width: 2; opacity: 0; }
    .sparkline.is-active .sparkline-dot { opacity: 1; }

    time { color: var(--ink-secondary); white-space: nowrap; }

    .table-view { margin-top: 16px; }

    .table-view summary {
        width: fit-content;
        color: var(--ink-secondary);
        font-size: 13px;
        cursor: pointer;
    }

    .table-view summary:hover { color: var(--ink); }
    .table-view .table-scroll { max-height: 360px; margin-top: 8px; overflow: auto; }
    .table-view thead th { position: sticky; top: 0; background: var(--surface); }
</style>
