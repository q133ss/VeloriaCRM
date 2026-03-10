<style>
    .admin-shell { display: grid; gap: 1.5rem; }
    .admin-hero {
        position: relative;
        overflow: hidden;
        padding: 1.75rem;
        border-radius: 1.5rem;
        background:
            radial-gradient(circle at top right, rgba(34, 197, 94, 0.16), transparent 28%),
            linear-gradient(135deg, rgba(14, 116, 144, 0.95), rgba(15, 23, 42, 0.92));
        color: #f8fafc;
    }
    .admin-hero h1 { font-size: clamp(1.8rem, 2.4vw, 2.6rem); margin-bottom: 0.65rem; color: inherit; }
    .admin-hero p { max-width: 52rem; margin-bottom: 0; color: rgba(248, 250, 252, 0.82); }
    .admin-subnav { display: flex; gap: 0.75rem; flex-wrap: wrap; }
    .admin-subnav-link {
        display: inline-flex;
        align-items: center;
        padding: 0.7rem 1rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.05);
        color: var(--bs-body-color);
        text-decoration: none;
        font-weight: 600;
    }
    .admin-subnav-link.is-active { background: rgba(var(--bs-primary-rgb), 0.14); color: var(--bs-primary); }
    .admin-grid { display: grid; gap: 1rem; }
    .admin-grid.metrics { grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); }
    .admin-panel {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1.25rem;
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
    }
    .admin-panel.soft { background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(255, 255, 255, 0.95)); }
    .admin-panel-body { padding: 1.25rem; }
    .admin-metric-label { color: #64748b; font-size: 0.86rem; margin-bottom: 0.45rem; }
    .admin-metric-value { font-size: 1.8rem; font-weight: 700; letter-spacing: -0.03em; }
    .admin-two-column { display: grid; gap: 1rem; grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr); }
    .admin-list { display: grid; gap: 0.75rem; }
    .admin-row {
        display: flex;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.95rem 1rem;
        border-radius: 1rem;
        background: rgba(148, 163, 184, 0.09);
    }
    .admin-row.is-clickable { cursor: pointer; border: 1px solid transparent; }
    .admin-row.is-clickable.is-active { border-color: rgba(var(--bs-primary-rgb), 0.35); background: rgba(var(--bs-primary-rgb), 0.08); }
    .admin-row-title { font-weight: 600; }
    .admin-row-meta { color: #64748b; font-size: 0.9rem; }
    .admin-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        background: rgba(15, 23, 42, 0.08);
        color: #0f172a;
        font-size: 0.82rem;
        font-weight: 600;
    }
    .admin-chip.success { background: rgba(34, 197, 94, 0.12); color: #166534; }
    .admin-chip.warning { background: rgba(245, 158, 11, 0.14); color: #92400e; }
    .admin-chip.danger { background: rgba(239, 68, 68, 0.14); color: #991b1b; }
    .admin-empty {
        padding: 2rem 1.25rem;
        border-radius: 1rem;
        border: 1px dashed rgba(148, 163, 184, 0.5);
        color: #64748b;
        text-align: center;
    }
    .admin-toolbar { display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center; }
    .admin-toolbar .form-control, .admin-toolbar .form-select { max-width: 280px; }
    .admin-detail-grid { display: grid; gap: 0.9rem; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); }
    .admin-detail-card { padding: 0.95rem 1rem; border-radius: 1rem; background: rgba(148, 163, 184, 0.08); }
    .admin-messages { display: grid; gap: 0.75rem; max-height: 420px; overflow: auto; }
    .admin-message { padding: 0.9rem 1rem; border-radius: 1rem; background: rgba(148, 163, 184, 0.09); }
    .admin-message.support { background: rgba(var(--bs-primary-rgb), 0.1); }
    .admin-stack { display: grid; gap: 1rem; }
    html[data-bs-theme="dark"] .admin-subnav-link { background: rgba(148, 163, 184, 0.12); color: #e2e8f0; }
    html[data-bs-theme="dark"] .admin-subnav-link.is-active { background: rgba(56, 189, 248, 0.18); color: #7dd3fc; }
    html[data-bs-theme="dark"] .admin-panel, html[data-bs-theme="dark"] .admin-panel.soft {
        background: rgba(15, 23, 42, 0.78);
        border-color: rgba(148, 163, 184, 0.14);
        box-shadow: none;
    }
    html[data-bs-theme="dark"] .admin-row,
    html[data-bs-theme="dark"] .admin-detail-card,
    html[data-bs-theme="dark"] .admin-message { background: rgba(148, 163, 184, 0.1); }
    html[data-bs-theme="dark"] .admin-chip { background: rgba(148, 163, 184, 0.14); color: #e2e8f0; }
    html[data-bs-theme="dark"] .admin-chip.success { color: #86efac; }
    html[data-bs-theme="dark"] .admin-chip.warning { color: #fcd34d; }
    html[data-bs-theme="dark"] .admin-chip.danger { color: #fda4af; }
    html[data-bs-theme="dark"] .admin-row-meta,
    html[data-bs-theme="dark"] .admin-metric-label,
    html[data-bs-theme="dark"] .admin-empty { color: #94a3b8; }
    @media (max-width: 991.98px) { .admin-two-column { grid-template-columns: 1fr; } }
</style>
