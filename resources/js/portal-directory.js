import $ from './jquery.js';
import { getDirectoryData, onMockDataChange } from './mock-data.js';
import { renderPagination } from './pagination.js';

const pageSize = 12;

// Search text and page. Search only ever covers the employee's own department: that is all the data holds.
const view = { query: '', page: 1 };

function matchingPeople(people) {
    const query = view.query.trim().toLowerCase();

    return people.filter((person) => !query || `${person.name} ${person.jobTitle} ${person.email}`.toLowerCase().includes(query));
}

function personCard(person) {
    return $('<div>', { class: 'col' }).append(
        $('<article>', { class: 'card directory-card' }).append(
            $('<span>', { class: 'profile-avatar', 'aria-hidden': 'true', text: person.initials }),
            $('<div>', { class: 'min-w-0' }).append(
                $('<h2>', { class: 'fs-6 fw-semibold mb-0 text-truncate', title: person.name, text: person.name }),
                $('<div>', { class: 'text-body-secondary text-truncate', title: person.jobTitle, text: person.jobTitle }),
                $('<a>', { class: 'd-block small text-break', href: `mailto:${person.email}`, text: person.email }),
            ),
        ),
    );
}

function renderDirectory($root) {
    const { employee, department, people } = getDirectoryData();
    const hasDepartment = employee && Boolean(department);

    // HR can delete the employee's record, or clear their department, for example in another tab.
    $root.find('[data-portal-missing]').toggleClass('d-none', employee);
    $root.find('[data-directory-no-department]').toggleClass('d-none', !employee || hasDepartment);
    $root.find('[data-portal-content]').toggleClass('d-none', !hasDepartment);
    $root.find('[data-directory-subtitle]').text(hasDepartment ? `Your colleagues in ${department}.` : 'Find your colleagues.');

    if (!hasDepartment) {
        $root.find('[data-directory-list]').empty();

        return;
    }

    const matches = matchingPeople(people);
    const pageCount = Math.max(1, Math.ceil(matches.length / pageSize));

    view.page = Math.min(view.page, pageCount);

    const start = (view.page - 1) * pageSize;
    const pageRows = matches.slice(start, start + pageSize);

    $root.find('[data-directory-list]').empty().append(...pageRows.map(personCard));
    $root.find('[data-directory-empty]').toggleClass('d-none', matches.length > 0);
    $root.find('[data-directory-summary]').text(
        `Showing ${matches.length ? start + 1 : 0} to ${start + pageRows.length} of ${matches.length} ${matches.length === 1 ? 'colleague' : 'colleagues'}`,
    );
    renderPagination($root.find('[data-directory-pagination]'), view.page, pageCount);
}

$(function () {
    const $directory = $('[data-page="portal-directory"]');

    if (!$directory.length) {
        return;
    }

    $directory.on('input', '[data-directory-search]', function () {
        view.query = String($(this).val());
        view.page = 1;
        renderDirectory($directory);
    });

    $directory.on('click', '[data-directory-clear]', function () {
        Object.assign(view, { query: '', page: 1 });
        $directory.find('[data-directory-search]').val('');
        renderDirectory($directory);
    });

    $directory.on('click', '[data-page-number]', function () {
        view.page = Number($(this).attr('data-page-number'));
        renderDirectory($directory);
    });

    renderDirectory($directory);
    onMockDataChange(() => renderDirectory($directory));
});
