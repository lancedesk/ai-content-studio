<?php
/**
 * Analytics Dashboard Template (Data-Driven)
 *
 * Uses REST API endpoints to load real generation analytics.
 *
 * @package AI_Content_Studio
 * @subpackage Admin/Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$rest_url = rest_url( 'acs/v1/analytics/' );
$nonce    = wp_create_nonce( 'wp_rest' );
?>

<div class="wrap acs-analytics-dashboard">
    <div class="acs-page-header">
        <h1><?php esc_html_e( 'Analytics Dashboard', 'ai-content-studio' ); ?></h1>
        <p class="acs-page-description">
            <?php esc_html_e( 'Track content generation metrics, costs, and performance over time.', 'ai-content-studio' ); ?>
        </p>
    </div>

    <!-- Filters -->
    <div class="acs-card acs-analytics-filters">
        <form id="acs-analytics-filters" class="acs-form">
            <div class="acs-form-row">
                <div class="acs-form-group">
                    <label for="acs-date-range"><?php esc_html_e( 'Date Range:', 'ai-content-studio' ); ?></label>
                    <select id="acs-date-range" class="acs-select">
                        <option value="7"><?php esc_html_e( 'Last 7 days', 'ai-content-studio' ); ?></option>
                        <option value="30" selected><?php esc_html_e( 'Last 30 days', 'ai-content-studio' ); ?></option>
                        <option value="90"><?php esc_html_e( 'Last 90 days', 'ai-content-studio' ); ?></option>
                    </select>
                </div>
                <div class="acs-form-group">
                    <label for="acs-provider-filter"><?php esc_html_e( 'Provider:', 'ai-content-studio' ); ?></label>
                    <select id="acs-provider-filter" class="acs-select">
                        <option value=""><?php esc_html_e( 'All Providers', 'ai-content-studio' ); ?></option>
                        <option value="groq">Groq</option>
                        <option value="openai">OpenAI</option>
                        <option value="anthropic">Anthropic</option>
                        <option value="mock">Mock</option>
                    </select>
                </div>
                <div class="acs-form-actions">
                    <button type="button" id="acs-refresh-analytics" class="button button-secondary">
                        <span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Refresh', 'ai-content-studio' ); ?>
                    </button>
                    <button type="button" id="acs-export-csv" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export CSV', 'ai-content-studio' ); ?>
                    </button>
                    <button type="button" id="acs-export-json" class="button button-secondary">
                        <span class="dashicons dashicons-download"></span> <?php esc_html_e( 'Export JSON', 'ai-content-studio' ); ?>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="acs-dashboard-grid acs-summary-cards">
        <div class="acs-card acs-stat-card" id="acs-stat-total">
            <h2><?php esc_html_e( 'Total Generations', 'ai-content-studio' ); ?></h2>
            <div class="acs-stat-value">—</div>
        </div>
        <div class="acs-card acs-stat-card" id="acs-stat-tokens">
            <h2><?php esc_html_e( 'Avg Tokens', 'ai-content-studio' ); ?></h2>
            <div class="acs-stat-value">—</div>
        </div>
        <div class="acs-card acs-stat-card" id="acs-stat-cost">
            <h2><?php esc_html_e( 'Est. Total Cost', 'ai-content-studio' ); ?></h2>
            <div class="acs-stat-value">—</div>
        </div>
    </div>

    <!-- Provider Breakdown -->
    <div class="acs-card" id="acs-provider-breakdown">
        <h2><?php esc_html_e( 'Provider Breakdown', 'ai-content-studio' ); ?></h2>
        <table class="wp-list-table widefat fixed striped" id="acs-provider-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Provider', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Generations', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Est. Cost', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Avg Time (s)', 'ai-content-studio' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="4"><?php esc_html_e( 'Loading...', 'ai-content-studio' ); ?></td></tr>
            </tbody>
        </table>
    </div>

    <!-- Chart -->
    <div class="acs-card acs-chart-card">
        <h2><?php esc_html_e( 'Generation Trends', 'ai-content-studio' ); ?></h2>
        <div class="acs-chart-container" style="position:relative;height:300px;">
            <canvas id="acs-generations-chart"></canvas>
        </div>
    </div>

    <!-- Recent Generations Table -->
    <div class="acs-card">
        <h2><?php esc_html_e( 'Recent Generations', 'ai-content-studio' ); ?></h2>
        <table class="wp-list-table widefat fixed striped" id="acs-generations-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'ID', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Provider', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Model', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Tokens', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Cost', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Time (s)', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'ai-content-studio' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'ai-content-studio' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr><td colspan="8"><?php esc_html_e( 'Loading...', 'ai-content-studio' ); ?></td></tr>
            </tbody>
        </table>
        <div class="tablenav bottom">
            <div class="tablenav-pages" id="acs-pagination"></div>
        </div>
    </div>
</div>

<style>
.acs-analytics-dashboard .acs-dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.acs-analytics-dashboard .acs-stat-card {
    text-align: center;
    padding: 20px;
}
.acs-analytics-dashboard .acs-stat-value {
    font-size: 2em;
    font-weight: 600;
    color: #2271b1;
}
.acs-analytics-dashboard .acs-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    padding: 16px;
    margin-bottom: 20px;
}
.acs-analytics-dashboard .acs-form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}
.acs-analytics-dashboard .acs-form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.acs-analytics-dashboard .acs-form-actions {
    display: flex;
    gap: 8px;
}
.acs-analytics-dashboard .acs-chart-container canvas {
    max-width: 100%;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function($){
    const REST_BASE = <?php echo wp_json_encode( $rest_url ); ?>;
    const NONCE = <?php echo wp_json_encode( $nonce ); ?>;

    let generationsChart = null;
    let currentPage = 1;

    function apiGet(endpoint, params) {
        const url = new URL(REST_BASE + endpoint, window.location.origin);
        Object.keys(params || {}).forEach(k => {
            if (params[k] !== '' && params[k] !== null) url.searchParams.append(k, params[k]);
        });
        return fetch(url.toString(), {
            headers: { 'X-WP-Nonce': NONCE }
        }).then(r => r.json());
    }

    function getFilters() {
        return {
            days: $('#acs-date-range').val(),
            provider: $('#acs-provider-filter').val()
        };
    }

    function loadSummary() {
        const f = getFilters();
        apiGet('summary', { provider: f.provider }).then(data => {
            $('#acs-stat-total .acs-stat-value').text(data.total_generations ?? 0);
            $('#acs-stat-tokens .acs-stat-value').text((data.avg_tokens ?? 0).toFixed(0));
            $('#acs-stat-cost .acs-stat-value').text('$' + (data.total_cost ?? 0).toFixed(4));

            const $tbody = $('#acs-provider-table tbody').empty();
            if (data.providers && data.providers.length) {
                data.providers.forEach(p => {
                    $tbody.append(`<tr>
                        <td>${p.provider || '—'}</td>
                        <td>${p.count || 0}</td>
                        <td>$${(parseFloat(p.cost) || 0).toFixed(4)}</td>
                        <td>${(parseFloat(p.avg_time) || 0).toFixed(2)}</td>
                    </tr>`);
                });
            } else {
                $tbody.append('<tr><td colspan="4">No data</td></tr>');
            }
        });
    }

    function loadChart() {
        const f = getFilters();
        apiGet('chart-data', { days: f.days, provider: f.provider }).then(data => {
            const ctx = document.getElementById('acs-generations-chart').getContext('2d');
            if (generationsChart) generationsChart.destroy();
            generationsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels || [],
                    datasets: [
                        {
                            label: 'Generations',
                            data: data.generations || [],
                            borderColor: '#2271b1',
                            backgroundColor: 'rgba(34,113,177,0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Cost ($)',
                            data: data.costs || [],
                            borderColor: '#00a32a',
                            backgroundColor: 'rgba(0,163,42,0.1)',
                            tension: 0.3,
                            yAxisID: 'yCost',
                            fill: false
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: 'Generations' } },
                        yCost: { position: 'right', beginAtZero: true, title: { display: true, text: 'Cost ($)' }, grid: { drawOnChartArea: false } }
                    }
                }
            });
        });
    }

    function loadGenerations(page) {
        page = page || 1;
        currentPage = page;
        const f = getFilters();
        apiGet('generations', { page: page, per_page: 15, provider: f.provider }).then(data => {
            const $tbody = $('#acs-generations-table tbody').empty();
            if (data.data && data.data.length) {
                data.data.forEach(row => {
                    $tbody.append(`<tr>
                        <td>${row.id}</td>
                        <td>${row.provider || '—'}</td>
                        <td>${row.model || '—'}</td>
                        <td>${row.tokens_used || '—'}</td>
                        <td>${row.cost_estimate ? '$' + parseFloat(row.cost_estimate).toFixed(4) : '—'}</td>
                        <td>${row.generation_time || '—'}</td>
                        <td>${row.status || '—'}</td>
                        <td>${row.created_at || '—'}</td>
                    </tr>`);
                });
            } else {
                $tbody.append('<tr><td colspan="8">No generations recorded yet.</td></tr>');
            }

            // Pagination
            const $pag = $('#acs-pagination').empty();
            if (data.pages > 1) {
                for (let i = 1; i <= data.pages; i++) {
                    const cls = i === currentPage ? 'button button-primary' : 'button';
                    $pag.append(`<button type="button" class="${cls}" data-page="${i}">${i}</button> `);
                }
            }
        });
    }

    function refreshAll() {
        loadSummary();
        loadChart();
        loadGenerations(1);
    }

    $(document).ready(function(){
        refreshAll();

        $('#acs-refresh-analytics').on('click', refreshAll);
        $('#acs-date-range, #acs-provider-filter').on('change', refreshAll);

        $('#acs-pagination').on('click', 'button[data-page]', function(){
            loadGenerations($(this).data('page'));
        });

        $('#acs-export-csv').on('click', function(){
            const f = getFilters();
            window.open(REST_BASE + 'export?format=csv&provider=' + encodeURIComponent(f.provider || '') + '&_wpnonce=' + NONCE, '_blank');
        });
        $('#acs-export-json').on('click', function(){
            const f = getFilters();
            window.open(REST_BASE + 'export?format=json&provider=' + encodeURIComponent(f.provider || '') + '&_wpnonce=' + NONCE, '_blank');
        });
    });
})(jQuery);
</script>
