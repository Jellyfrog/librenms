import Alpine from "alpinejs";
import {
    columnVisibilityFeature,
    createFilteredRowModel,
    createPaginatedRowModel,
    createSortedRowModel,
    createTable,
    filterFn_includesString,
    functionalUpdate,
    globalFilteringFeature,
    rowPaginationFeature,
    rowSortingFeature,
    sortFn_alphanumeric,
    sortFn_basic,
    sortFn_text,
    tableFeatures,
} from "@tanstack/alpine-table";

const features = tableFeatures({
    globalFilteringFeature,
    rowSortingFeature,
    rowPaginationFeature,
    columnVisibilityFeature,
    filteredRowModel: createFilteredRowModel(),
    sortedRowModel: createSortedRowModel(),
    paginatedRowModel: createPaginatedRowModel(),
    filterFns: { includesString: filterFn_includesString },
    sortFns: { alphanumeric: sortFn_alphanumeric, basic: sortFn_basic, text: sortFn_text },
});

// page size used for "all rows" in local mode, the server gets -1
const ALL_ROWS = Number.MAX_SAFE_INTEGER;

const storage = {
    get(key) {
        try {
            return JSON.parse(window.localStorage.getItem(key));
        } catch {
            return null;
        }
    },
    set(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch {
            // storage is a convenience, ignore private mode and quota errors
        }
    },
};

/**
 * Reads the column definitions from the x-data-table.column cells in the row template.
 *
 * @param {HTMLElement} root
 * @returns {Array<{id: string, label: string, sortable: boolean, searchable: boolean, hideable: boolean, visible: boolean, width: string|null, headerClass: string, headerTitle: string|null}>}
 */
function readColumns(root) {
    const template = root.querySelector("template[data-table-row]");
    if (!template) {
        throw new Error("x-data-table: no row template found");
    }

    return Array.from(template.content.querySelectorAll("[data-column]")).map((cell) => ({
        id: cell.dataset.column,
        label: cell.dataset.label ?? "",
        sortable: cell.dataset.sortable === "1",
        searchable: cell.dataset.searchable === "1",
        hideable: cell.dataset.hideable === "1",
        visible: cell.dataset.visible !== "0",
        width: cell.dataset.width || null,
        headerClass: cell.dataset.headerClass ?? "",
        headerTitle: cell.dataset.headerTitle ?? null,
    }));
}

/**
 * Sortable, filterable, paginated table for the x-data-table blade component.
 *
 * mode "server": every sort, search, filter and page change GETs config.url with
 *   {...params, ...requestParams(), filter: {field: {op: value}}, sort: {column: "asc"|"desc"}, search, page, per_page (-1 = all)}
 *   and expects a Laravel resource collection of a paginator: {data: [...], meta: {current_page, last_page, per_page, total}}
 *   See App\Http\Requests\DataTableRequest. The sort object is in priority order (shift+click adds a column).
 * mode "local": rows come from config.rows, sorting, search and pagination run in the browser.
 *   x-filter filters are only supported in server mode.
 *
 * Page specific behavior: Alpine.data("ports", (config) => LibreNMS.dataTable(config, {...properties}))
 * and <x-data-table component="ports">. The properties override the base ones, getters are kept intact,
 * a page init() runs after the base init.
 * Hooks: requestParams() extra request params, loaded(json) handles extra response data (resource ->additional()).
 *
 * Selection (config.selectable): rows are selected by rowKey across pages, "all matching" selects every row of the filter.
 * selectionData() returns {ids: [...]} or {all: true, filter: {...}} for bulk action requests.
 *
 * Events: listens for "data-table:reload" on window ({name}).
 *
 * @param {object} config see resources/views/components/data-table/index.blade.php
 * @param {object} component page specific properties
 */
export default function dataTable(config, component = {}) {
    const local = config.mode === "local";
    const rowKey = config.rowKey ?? "id";
    const storageKey = config.name ? `librenms.data-table.${config.name}` : null;
    const saved = (storageKey && storage.get(storageKey)) || {};
    const perPage = Number(saved.perPage ?? config.perPage ?? 50);

    // rows are kept out of the reactive state: TanStack reads every row while sorting and searching,
    // through a reactive proxy each read would be tracked. dataVersion tells Alpine the rows changed.
    let data = [];
    const state = Alpine.reactive({
        dataVersion: 0,
        total: 0,
        sorting: Object.entries(config.sort ?? {}).map(([id, dir]) => ({ id, desc: dir === "desc" })),
        pagination: { pageIndex: 0, pageSize: perPage < 0 ? ALL_ROWS : perPage },
        columnVisibility: {},
        globalFilter: "",
        filter: config.filter ?? {},
    });

    const keyOf = (row) => (row[rowKey] === undefined || row[rowKey] === null ? null : String(row[rowKey]));
    const getRowId = (row, index) => keyOf(row) ?? `#${index}`;
    // row id => row object, see the rows getter
    let rowsById = new Map();
    const setData = (rows) => {
        data = rows;
        rowsById = new Map(data.map((row, index) => [getRowId(row, index), row]));
        state.dataVersion++;
    };
    setData(local && Array.isArray(config.rows) ? config.rows : []);
    // aborts the previous server request when a newer one starts
    let controller = null;
    let noticeTimer = null;

    const base = {
        config,
        lang: config.lang ?? {},
        state,
        columns: [],
        table: null,
        perPage,
        loading: false,
        error: null,
        // selection, row key => true
        selected: {},
        allMatching: false,
        // request keys in flight, see request()
        pending: {},
        notice: null,

        init() {
            this.columns = readColumns(this.$root);
            this.columns.forEach((column) => {
                const visible = saved.columns?.[column.id] ?? column.visible;
                if (!visible) {
                    state.columnVisibility[column.id] = false;
                }
            });

            this.table = createTable({
                features,
                columns: this.columns.map((column) => ({
                    id: column.id,
                    accessorFn: (row) => row[column.id],
                    enableSorting: column.sortable,
                    enableHiding: column.hideable,
                    enableGlobalFilter: column.searchable,
                    sortFn: "alphanumeric",
                    sortUndefined: "last",
                })),
                get data() {
                    state.dataVersion; // dependency for Alpine effects
                    return data;
                },
                get rowCount() {
                    return local ? undefined : state.total;
                },
                getRowId,
                manualSorting: !local,
                manualFiltering: !local,
                manualPagination: !local,
                // changed() resets the page itself, TanStack's deferred reset would undo a page change made right after
                autoResetPageIndex: false,
                enableMultiSort: true,
                enableSortingRemoval: true,
                globalFilterFn: "includesString",
                getColumnCanGlobalFilter: (column) => column.columnDef.enableGlobalFilter === true,
                state: {
                    get sorting() {
                        return state.sorting;
                    },
                    get pagination() {
                        return state.pagination;
                    },
                    get columnVisibility() {
                        return state.columnVisibility;
                    },
                    get globalFilter() {
                        return state.globalFilter;
                    },
                },
                onSortingChange: (updater) => {
                    state.sorting = functionalUpdate(updater, state.sorting);
                    this.changed(true);
                },
                onPaginationChange: (updater) => {
                    state.pagination = functionalUpdate(updater, state.pagination);
                    this.changed(false);
                },
                onColumnVisibilityChange: (updater) => {
                    state.columnVisibility = functionalUpdate(updater, state.columnVisibility);
                    this.save();
                },
            });

            this.$watch("state.globalFilter", () => this.changed(true));
            this.$watch("perPage", (value) => {
                state.pagination = { pageIndex: 0, pageSize: Number(value) < 0 ? ALL_ROWS : Number(value) };
                this.save();
                this.changed(false);
            });

            if (!local) {
                this.load();
            }
        },

        /**
         * Rows on the current page.
         * Returns the plain row objects instead of TanStack's row.original: those are wrapped in a proxy
         * that makes every cell binding re-run (and every x-html re-render) on any table state change.
         * With stable objects x-for only moves rows and the cells stay untouched.
         */
        get rows() {
            return this.table.getRowModel().rows.map((row) => rowsById.get(row.id));
        },

        /**
         * x-for key: rowKey, or the position on the page when a row has no rowKey.
         * Computed from the value, Alpine's scope hands the template proxies that are not the objects in rowsById.
         */
        rowId(row, index) {
            return getRowId(row, index);
        },

        get total() {
            return local ? this.table.getPrePaginatedRowModel().rows.length : state.total;
        },

        get pageCount() {
            return Math.max(1, this.table.getPageCount());
        },

        get page() {
            return state.pagination.pageIndex + 1;
        },

        get firstItem() {
            return this.total === 0 ? 0 : state.pagination.pageIndex * state.pagination.pageSize + 1;
        },

        get lastItem() {
            return Math.min((state.pagination.pageIndex + 1) * state.pagination.pageSize, this.total);
        },

        isVisible(id) {
            return state.columnVisibility[id] !== false;
        },

        get visibleColumnCount() {
            return this.columns.filter((column) => this.isVisible(column.id)).length + (config.selectable ? 1 : 0) || 1;
        },

        toggleColumn(id) {
            this.table.getColumn(id).toggleVisibility();
        },

        /** Show or hide a column from page code, not remembered like the user's choice */
        setColumnVisible(id, visible) {
            state.columnVisibility = { ...state.columnVisibility, [id]: visible };
        },

        /** Click toggles the column sort, shift+click adds it to the sort (multi sort) */
        sortBy(id, event) {
            this.table.getColumn(id).getToggleSortingHandler()?.(event);
        },

        /** @returns {false|"asc"|"desc"} */
        sortDirection(id) {
            const sort = state.sorting.find((item) => item.id === id);

            return sort ? (sort.desc ? "desc" : "asc") : false;
        },

        /** 1 based position in a multi column sort, null when the column is not sorted or the only sort */
        sortPosition(id) {
            const index = state.sorting.findIndex((sort) => sort.id === id);

            return index === -1 || state.sorting.length < 2 ? null : index + 1;
        },

        goTo(page) {
            this.table.setPageIndex(Math.min(Math.max(page, 1), this.pageCount) - 1);
        },

        applyFilter(filter) {
            // x-filter dispatches its initial filter on init, skip the duplicate load
            if (JSON.stringify(filter ?? {}) === JSON.stringify(state.filter)) {
                return;
            }
            state.filter = filter ?? {};
            this.clearSelection();
            this.changed(true);
        },

        /**
         * State changed, reset to the first page when the result set changed and reload in server mode.
         * Call it after changing a requestParams() value.
         * @param {boolean} resetPage
         */
        changed(resetPage) {
            if (resetPage && state.pagination.pageIndex !== 0) {
                state.pagination = { ...state.pagination, pageIndex: 0 };
            }
            if (!local) {
                this.load();
            }
        },

        save() {
            if (!storageKey) {
                return;
            }
            const columns = {};
            this.columns.forEach((column) => {
                columns[column.id] = this.isVisible(column.id);
            });
            storage.set(storageKey, { perPage: Number(this.perPage), columns });
        },

        /** Hook: extra request params for this page */
        requestParams() {
            return {};
        },

        /** Hook: the server response, for data added with ->additional() */
        loaded(json) {},

        params() {
            const params = {
                ...(config.params ?? {}),
                ...this.requestParams(),
                filter: state.filter,
                page: state.pagination.pageIndex + 1,
                per_page: state.pagination.pageSize === ALL_ROWS ? -1 : state.pagination.pageSize,
            };
            if (state.globalFilter) {
                params.search = state.globalFilter;
            }
            // leave sort out when empty so the endpoint's default sort applies
            if (state.sorting.length) {
                params.sort = Object.fromEntries(state.sorting.map((sort) => [sort.id, sort.desc ? "desc" : "asc"]));
            }

            return params;
        },

        /** Reload rows from the server, local mode has nothing to reload */
        reload() {
            if (!local) {
                this.load();
            }
        },

        load() {
            controller?.abort();
            const current = (controller = new AbortController());
            this.loading = true;
            this.error = null;

            return window.axios
                .get(config.url, { params: this.params(), signal: current.signal })
                .then(({ data: json }) => {
                    const meta = json.meta ?? {};
                    setData(json.data ?? []);
                    state.total = meta.total ?? data.length;
                    if (meta.current_page && meta.current_page - 1 !== state.pagination.pageIndex) {
                        state.pagination = { ...state.pagination, pageIndex: meta.current_page - 1 };
                    }
                    this.loaded(json);
                })
                .catch((error) => {
                    if (current === controller && !window.axios.isCancel(error)) {
                        this.error = errorMessage(error, this.lang);
                    }
                })
                .finally(() => {
                    if (current === controller) {
                        this.loading = false;
                    }
                });
        },

        /**
         * Send a request for an action, failures show a toast and reject.
         * isPending(key) is true while it runs, use it to disable the control.
         */
        request(key, method, url, data = undefined) {
            this.pending[key] = true;

            return window
                .axios({ method, url, data })
                .then((response) => response.data)
                .catch((error) => {
                    window.toastr?.error(errorMessage(error, this.lang));
                    throw error;
                })
                .finally(() => delete this.pending[key]);
        },

        isPending(key) {
            return this.pending[key] === true;
        },

        /** Show the result of an action where the selection bar was, selecting rows again hides it */
        notify(message, timeout = 4000) {
            clearTimeout(noticeTimer);
            this.notice = message;
            noticeTimer = setTimeout(() => (this.notice = null), timeout);
        },

        // --- Selection ---

        get selectionCount() {
            return this.allMatching ? this.total : Object.keys(this.selected).length;
        },

        get pageSelected() {
            const rows = this.rows;

            return rows.length > 0 && rows.every((row) => this.isSelected(row));
        },

        get pageHasSelection() {
            return this.rows.some((row) => this.isSelected(row));
        },

        isSelected(row) {
            return this.allMatching || this.selected[keyOf(row)] === true;
        },

        toggleSelected(row, checked) {
            if (this.allMatching) {
                // drop back to selecting the rows that are visible
                this.allMatching = false;
                this.rows.forEach((r) => (this.selected[keyOf(r)] = true));
            }

            if (checked) {
                this.selected[keyOf(row)] = true;
            } else {
                delete this.selected[keyOf(row)];
            }
        },

        selectPage(checked) {
            if (this.allMatching) {
                this.clearSelection();
                return;
            }

            this.rows.forEach((row) => (checked ? (this.selected[keyOf(row)] = true) : delete this.selected[keyOf(row)]));
        },

        selectAllMatching() {
            this.allMatching = true;
        },

        clearSelection() {
            this.selected = {};
            this.allMatching = false;
        },

        /** Request data for the selection: every row matching the filter or the selected row keys */
        selectionData() {
            return this.allMatching
                ? { all: true, filter: state.filter, ...(config.params ?? {}), ...this.requestParams() }
                : { ids: Object.keys(this.selected).map((key) => (/^\d+$/.test(key) ? Number(key) : key)) };
        },
    };

    // a page init() runs after the table is set up
    const { init: pageInit, ...descriptors } = Object.getOwnPropertyDescriptors(component);
    if (pageInit) {
        const baseInit = base.init;
        base.init = function () {
            baseInit.call(this);
            pageInit.value.call(this);
        };
    }

    return Object.defineProperties(base, descriptors);
}

function errorMessage(error, lang) {
    return error.response?.data?.message ?? lang.request_failed ?? "Request failed";
}
