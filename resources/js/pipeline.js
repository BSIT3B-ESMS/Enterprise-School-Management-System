import $ from './jquery.js';
import { statusClass } from './recruitment-workflow.js';

function percentOf(value, total) {
    return `${Math.round((value / total) * 10000) / 100}%`;
}

/**
 * Fills a `.progress-stacked` element with one colored segment per non-empty stage
 * (colors come from the `.stage-<name>` classes in _dashboard.scss).
 *
 * @param {Array<{ stage: string, count: number }>} stages
 */
export function renderPipelineBar($bar, stages) {
    const total = stages.reduce((sum, entry) => sum + entry.count, 0);

    $bar.empty().append(...stages
        .filter((entry) => entry.count > 0)
        .map(({ stage, count }) => $('<div>', {
            class: 'progress',
            role: 'progressbar',
            'aria-label': stage,
            'aria-valuenow': count,
            'aria-valuemin': 0,
            'aria-valuemax': total,
        }).css('width', percentOf(count, total)).append(
            $('<div>', { class: `progress-bar ${statusClass(stage)}`, title: `${stage}: ${count}` }),
        )));
}
