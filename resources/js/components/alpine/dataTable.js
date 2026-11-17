import Alpine from "alpinejs";
import {
    columnFilteringFeature,
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
import { matchesFilters } from "./dataTableFilters.js";

const features = tableFeatures({
    columnFilteringFeature,
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

// hidden column that applies the x-filter filters in local mode
const FILTER_COLUMN = "__filters";
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
 * @returns {Array<{id: string, label: string, sortable: boolean, searchable: boolean, hideable: boolean, visible: boolean, width: string|null, headerClass: string}>}
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
    }));
}

/**
 * Sortable, filterable, paginated table for the x-data-table blade component.
 *
 * mode "server": every sort, search, filter and page change POSTs to config.url:
 *   {...params, filter: {field: {op: value}}, sort: {column: "asc"|"desc"}, search, page, per_page (-1 = all)}
 *   and expects Laravel paginator json: {data: [...], current_page, last_page, per_page, total}
 *   The sort object is in priority order (shift+click adds a column).
 * mode "local": rows come from config.rows or are fetched once from config.url with per_page -1,
 *   then sorting, search, x-filter filters and pagination run in the browser.
 *
 * Events: listens for "data-table:reload" on window ({name}), dispatches "data-table:loaded" ({name, response}).
 *
 * @param {object} config see resources/views/components/data-table/index.blade.php
 */
export default function dataTable(config) {
    const local = config.mode === "local";
    const rowKey = config.rowKey ?? "id";
    const storageKey = config.name ? `librenms.data-table.${config.name}` : null;
    const saved = (storageKey && storage.get(storageKey)) || {};
    const perPage = Number(saved.perPage ?? config.perPage ?? 50);

    const state = Alpine.reactive({
        data: local && Array.isArray(config.rows) ? config.rows : [],
        total: 0,
        sorting: Object.entries(config.sort ?? {}).map(([id, dir]) => ({ id, desc: dir === "desc" })),
        pagination: { pageIndex: 0, pageSize: perPage < 0 ? ALL_ROWS : perPage },
        columnVisibility: { [FILTER_COLUMN]: false },
        globalFilter: "",
        filter: config.filter ?? {},
    });

    const getRowId = (row, index) => String(row[rowKey] ?? index);
    // row id => row object as read from state.data, see the rows getter
    let rowsById = new Map();
    const setData = (rows) => {
        state.data = rows;
        rowsById = new Map(state.data.map((row, index) => [getRowId(row, index), row]));
    };
    setData(state.data);

    return {
        config,
        state,
        columns: [],
        table: null,
        perPage,
        search: "",
        loading: false,
        error: null,
        loadId: 0,

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
                columns: [
                    ...this.columns.map((column) => ({
                        id: column.id,
                        accessorFn: (row) => row[column.id],
                        enableSorting: column.sortable,
                        enableHiding: column.hideable,
                        enableGlobalFilter: column.searchable,
                        sortFn: "alphanumeric",
                        sortUndefined: "last",
                    })),
                    {
                        id: FILTER_COLUMN,
                        accessorFn: (row) => row,
                        enableHiding: false,
                        enableSorting: false,
                        enableGlobalFilter: false,
                        filterFn: (row, columnId, filters) => matchesFilters(row.original, filters),
                    },
                ],
                get data() {
                    return state.data;
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
                    get columnFilters() {
                        return Object.keys(state.filter).length ? [{ id: FILTER_COLUMN, value: state.filter }] : [];
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
                onGlobalFilterChange: (updater) => {
                    state.globalFilter = functionalUpdate(updater, state.globalFilter) ?? "";
                },
                // the filter column only exists to apply x-filter filters, ignore table driven changes to it
                onColumnFiltersChange: () => {},
            });

            this.$watch("search", (value) => {
                state.globalFilter = value;
                this.changed(true);
            });
            this.$watch("perPage", (value) => {
                state.pagination = { pageIndex: 0, pageSize: Number(value) < 0 ? ALL_ROWS : Number(value) };
                this.save();
                this.changed(false);
            });

            if (!local || !Array.isArray(config.rows)) {
                this.load();
            }
        },

        /**
         * Rows on the current page, as sent by the server.
         * Returns the same objects as state.data instead of TanStack's row.original: those are wrapped in a proxy
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
            return row[rowKey] === undefined || row[rowKey] === null ? `#${index}` : String(row[rowKey]);
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
            return this.columns.filter((column) => this.isVisible(column.id)).length || 1;
        },

        toggleColumn(id) {
            this.table.getColumn(id).toggleVisibility();
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
            this.changed(true);
        },

        /**
         * State changed, reset to the first page when the result set changed and reload in server mode.
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

        requestBody() {
            const pageSize = state.pagination.pageSize === ALL_ROWS ? -1 : state.pagination.pageSize;
            const body = {
                ...(config.params ?? {}),
                filter: state.filter,
                search: state.globalFilter,
                page: state.pagination.pageIndex + 1,
                per_page: pageSize,
            };
            // leave sort out when empty so the controller's default sort applies
            if (state.sorting.length) {
                body.sort = Object.fromEntries(state.sorting.map((sort) => [sort.id, sort.desc ? "desc" : "asc"]));
            }

            return body;
        },

        /** Reload rows from the server, in local mode this refetches every row */
        reload() {
            if (config.url) {
                this.load();
            }
        },

        load() {
            const loadId = ++this.loadId;
            this.loading = true;
            this.error = null;

            // in local mode the browser sorts, searches and filters, the server only applies params
            const body = local ? { ...(config.params ?? {}), page: 1, per_page: -1 } : this.requestBody();

            return fetch(config.url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content ?? "",
                },
                body: JSON.stringify(body),
            })
                .then(async (response) => {
                    const json = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(json.message ?? config.lang?.request_failed ?? "Request failed");
                    }

                    return json;
                })
                .then((json) => {
                    if (loadId !== this.loadId) {
                        return; // a newer request is in flight
                    }
                    setData(json.data ?? []);
                    state.total = json.total ?? state.data.length;
                    if (!local && json.current_page && json.current_page - 1 !== state.pagination.pageIndex) {
                        state.pagination = { ...state.pagination, pageIndex: json.current_page - 1 };
                    }
                    this.$dispatch("data-table:loaded", { name: config.name, response: json });
                })
                .catch((error) => {
                    if (loadId === this.loadId) {
                        this.error = error.message;
                    }
                })
                .finally(() => {
                    if (loadId === this.loadId) {
                        this.loading = false;
                    }
                });
        },
    };
}
