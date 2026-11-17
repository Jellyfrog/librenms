/**
 * Evaluates x-filter filters ({field: {op: value}}) against a plain row object in the browser.
 * Mirrors App\Models\Traits\Filterable so a table filters the same in local and server mode.
 * Fields use dot notation for nested values (location.location). Custom server side filters
 * (Model::filterXxx methods) have no local equivalent, use server mode for those.
 */

const isEmpty = (value) => value === null || value === undefined || value === "";

const toList = (value) => (Array.isArray(value) ? value : String(value).split(","));

const compare = (a, b) => {
    const na = Number(a);
    const nb = Number(b);
    if (!isEmpty(a) && !isEmpty(b) && !Number.isNaN(na) && !Number.isNaN(nb)) {
        return na - nb;
    }

    return String(a ?? "").localeCompare(String(b ?? ""), undefined, { numeric: true, sensitivity: "base" });
};

// SQL LIKE in MySQL is case insensitive with the default collation
const lower = (value) => String(value ?? "").toLowerCase();

export const filterOperators = {
    eq: (value, target) => !isEmpty(value) && compare(value, target) === 0,
    neq: (value, target) => isEmpty(value) || compare(value, target) !== 0,
    gt: (value, target) => !isEmpty(value) && compare(value, target) > 0,
    gte: (value, target) => !isEmpty(value) && compare(value, target) >= 0,
    lt: (value, target) => !isEmpty(value) && compare(value, target) < 0,
    lte: (value, target) => !isEmpty(value) && compare(value, target) <= 0,
    contains: (value, target) => lower(value).includes(lower(target)),
    not_contains: (value, target) => !lower(value).includes(lower(target)),
    starts_with: (value, target) => lower(value).startsWith(lower(target)),
    ends_with: (value, target) => lower(value).endsWith(lower(target)),
    is_empty: (value) => isEmpty(value),
    is_not_empty: (value) => !isEmpty(value),
    in: (value, target) => toList(target).some((item) => compare(value, item) === 0),
    not_in: (value, target) => !toList(target).some((item) => compare(value, item) === 0),
};

export function getField(row, field) {
    return String(field)
        .split(".")
        .reduce((value, key) => (value === null || value === undefined ? undefined : value[key]), row);
}

/**
 * @param {object} row
 * @param {Object<string, Object<string, *>>} filters x-filter formattedFilters
 * @returns {boolean} true when the row matches every filter, unknown operators are ignored like on the server
 */
export function matchesFilters(row, filters) {
    return Object.entries(filters ?? {}).every(([field, operators]) =>
        Object.entries(operators ?? {}).every(([op, target]) => {
            const operator = filterOperators[op];

            return operator === undefined || operator(getField(row, field), target);
        }),
    );
}
