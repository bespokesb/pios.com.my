import { OrgChart } from 'd3-org-chart';

/**
 * Owner's partner-network org chart (d3-org-chart): centered tree with pan/
 * zoom, expand/collapse, client-side search, and rich per-partner node cards.
 * Data is embedded by the Filament page as JSON; nodes link to the partner
 * edit screen.
 */
const STATUS_COLORS = {
    active: '#16a34a',
    pending: '#d97706',
    suspended: '#6b7280',
    inactive: '#dc2626',
};

const nodeCard = (d) => {
    const n = d.data;

    if (n.isRoot) {
        return `
            <div style="width:${d.width}px;height:${d.height}px;display:flex;align-items:center;justify-content:center;gap:10px;
                        background:linear-gradient(135deg,#174A78,#215F9A);border-radius:14px;color:#fff;
                        font-family:ui-sans-serif,system-ui,sans-serif;box-shadow:0 6px 16px rgba(23,74,120,.35);">
                <div style="font-weight:700;font-size:15px;">${n.name}</div>
                <div style="font-size:11px;opacity:.85;">${n.subtitle}</div>
            </div>`;
    }

    const statusColor = STATUS_COLORS[n.status] ?? '#6b7280';
    const slotColor = n.hasOpenSlot ? '#16a34a' : '#dc2626';

    return `
        <div style="width:${d.width}px;height:${d.height}px;background:#fff;border:1px solid #e5e7eb;border-radius:14px;
                    box-shadow:0 4px 10px rgba(15,23,42,.08);padding:10px 12px;box-sizing:border-box;
                    font-family:ui-sans-serif,system-ui,sans-serif;overflow:hidden;">
            <div style="display:flex;align-items:center;gap:9px;">
                <div style="width:34px;height:34px;border-radius:9999px;background:${statusColor}1a;color:${statusColor};
                            display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex:none;">
                    ${n.initials}
                </div>
                <div style="min-width:0;">
                    <div style="font-weight:600;font-size:13px;color:#0f172a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${n.name}</div>
                    <div style="font-family:ui-monospace,monospace;font-size:10px;color:#64748b;">${n.partnerNo}${n.tier ? ' · ' + n.tier : ''}</div>
                </div>
                <span style="margin-inline-start:auto;flex:none;font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;
                             color:${statusColor};background:${statusColor}14;border:1px solid ${statusColor}33;
                             border-radius:9999px;padding:2px 7px;">${n.status}</span>
            </div>
            <div style="display:flex;gap:6px;margin-top:9px;">
                <div style="flex:1;text-align:center;background:#f8fafc;border-radius:8px;padding:4px 2px;">
                    <div style="font-size:12px;font-weight:700;color:${slotColor};">${n.used}/${n.max}</div>
                    <div style="font-size:8.5px;color:#94a3b8;">${n.labels.slots}</div>
                </div>
                <div style="flex:1;text-align:center;background:#f8fafc;border-radius:8px;padding:4px 2px;">
                    <div style="font-size:12px;font-weight:700;color:#0f172a;">${n.downline}</div>
                    <div style="font-size:8.5px;color:#94a3b8;">${n.labels.downline}</div>
                </div>
                <div style="flex:1;text-align:center;background:#f8fafc;border-radius:8px;padding:4px 2px;">
                    <div style="font-size:12px;font-weight:700;color:#0f172a;">${n.bos}</div>
                    <div style="font-size:8.5px;color:#94a3b8;">${n.labels.bos}</div>
                </div>
                <div style="flex:1;text-align:center;background:#f8fafc;border-radius:8px;padding:4px 2px;">
                    <div style="font-size:12px;font-weight:700;color:#0f172a;">${n.customers}</div>
                    <div style="font-size:8.5px;color:#94a3b8;">${n.labels.customers}</div>
                </div>
            </div>
        </div>`;
};

const init = () => {
    const container = document.getElementById('partner-tree');
    const payload = document.getElementById('partner-tree-data');

    if (! container || ! payload || container.dataset.initialised) {
        return;
    }

    container.dataset.initialised = '1';
    const nodes = JSON.parse(payload.textContent);

    const chart = new OrgChart()
        .container('#partner-tree')
        .data(nodes)
        // No compact stacking: a partner's direct downline (max 4) always
        // renders side by side on ONE row.
        .compact(false)
        .nodeWidth(() => 265)
        .nodeHeight((d) => (d.data.isRoot ? 56 : 104))
        .childrenMargin(() => 56)
        .neighbourMargin(() => 28)
        .siblingsMargin(() => 28)
        .buttonContent(({ node }) => `
            <div style="border-radius:9999px;background:#fff;border:1px solid #cbd5e1;color:#475569;
                        font-size:10px;padding:3px 9px;font-family:ui-sans-serif,system-ui,sans-serif;box-shadow:0 1px 3px rgba(15,23,42,.12);">
                ${node.children ? '−' : '+' + node.data._directSubordinates}
            </div>`)
        .nodeContent(nodeCard)
        .onNodeClick((d) => {
            if (d.data.url) {
                window.location.href = d.data.url;
            }
        })
        .render();

    chart.expandAll().fit();

    document.getElementById('pt-expand')?.addEventListener('click', () => chart.expandAll().fit());
    document.getElementById('pt-collapse')?.addEventListener('click', () => chart.collapseAll().fit());
    document.getElementById('pt-fit')?.addEventListener('click', () => chart.fit());

    const search = document.getElementById('pt-search');
    search?.addEventListener('input', (event) => {
        const term = event.target.value.trim().toLowerCase();

        chart.clearHighlighting();

        if (term === '') {
            return;
        }

        const data = chart.data();
        let firstHit = null;

        data.forEach((d) => {
            d._expanded = false;
            d._highlighted = false;
        });

        data.forEach((d) => {
            if (d.isRoot) {
                return;
            }

            const haystack = `${d.name} ${d.partnerNo}`.toLowerCase();

            if (haystack.includes(term)) {
                d._highlighted = true;
                d._expanded = true;
                firstHit ??= d;
            }
        });

        chart.data(data).render();

        if (firstHit) {
            chart.setCentered(firstHit.id).render();
        }
    });
};

document.addEventListener('DOMContentLoaded', init);
document.addEventListener('livewire:navigated', init);
