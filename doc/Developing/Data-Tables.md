# Data Tables

Every list of records in the web UI uses one table: the `x-data-table` Blade component.
It sorts (shift+click a header to sort on more columns), searches, filters with `x-filter`,
pages, remembers column and page size choices, switches to cards on small screens and can select rows for bulk actions.

It has two modes:

- **server** (default): every change requests a page from an endpoint. Use it for anything that can grow large.
- **local**: the page hands over every row and the browser sorts, searches and pages them. Use it for small lists.

## Server mode

The endpoint takes a `DataTableRequest` and returns an API resource collection of a paginator.

```php
use App\Http\Requests\DataTableRequest;
use App\Http\Resources\Table\EventlogResource;

public function __invoke(DataTableRequest $request): AnonymousResourceCollection
{
    $request->validate(['device' => 'nullable|integer', ...Eventlog::filterValidationRules()]);

    $query = Eventlog::hasAccess($request->user())
        ->with('device')
        ->applyFilters($request->filters())
        ->when($request->search(), fn ($q, $search) => $q->where('message', 'like', "%$search%"));

    // sortable keys: a column, or key => column, expression or fn ($query, $direction)
    $request->sortQuery($query, ['datetime', 'type', 'message'], ['datetime' => 'desc']);

    return EventlogResource::collection($request->paginate($query));
}
```

The request is a GET query string: `page`, `per_page` (`-1` returns every row), `search`,
`sort[column]=asc|desc` (in priority order) and `filter[field][op]=value` for `Filterable` models.
A page past the end returns the last page. Extra response data goes in `->additional([...])`.

The resource formats one row. Keep values as text where possible, html only when the server has to build it (links, labels).

```blade
<x-data-table name="eventlog" :url="route('table.eventlog')" :sort="['datetime' => 'desc']">
    <x-data-table.column field="datetime" :label="__('Timestamp')" html />
    <x-data-table.column field="type" :label="__('Type')" html />
    <x-data-table.column field="message" :label="__('Message')" />
</x-data-table>
```

## Local mode

Pass the rows through the same kind of resource:

```blade
<x-data-table name="authlog" mode="local" :rows="AuthLogResource::collection($logs)->resolve()">
```

## Columns

`<x-data-table.column>` is one cell of the row template, the current row is `row`.
Without a slot it shows the escaped field, `html` renders the field as html, or give your own cell content:

```blade
<x-data-table.column field="ifSpeed" :label="__('Speed')" x-text="LibreNMS.Number.formatSi(row.ifSpeed, 2, 0, 'bps')" />
<x-data-table.column field="ifName" :label="__('Port')" :hideable="false">
    <a :href="row.url" x-text="row.label"></a>
</x-data-table.column>
```

Options: `sortable`, `hideable`, `visible` (initial), `searchable` (local mode), `width`, `header-class`, `header-title`.
Other attributes go on the `td`. Hidden columns are not rendered.

## Page specific behavior and bulk actions

Extend the table component and pass its name:

```blade
<x-data-table component="portGroupPorts" :url="..." row-key="port_id" :config="['updateUrl' => ...]">
    <x-slot:actions>
        <button x-on:click="remove()" :disabled="isPending('update')">Remove</button>
    </x-slot:actions>
    ...
</x-data-table>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('portGroupPorts', (config) => LibreNMS.dataTable(config, {
        requestParams() { return {membership: this.membership}; },
        loaded(json) { this.members = json.members; },
        remove() {
            this.request('update', 'POST', config.updateUrl, this.selectionData())
                .then((response) => { this.clearSelection(); this.notify(response.message); this.load(); })
                .catch(() => {});
        },
    }));
});
</script>
```

An `actions` slot makes rows selectable. `selectionData()` returns `{ids: [...]}` or, after "select all matching",
`{all: true, filter: {...}, ...params}` so the endpoint can apply the action to the same query.

## Row details and row actions

An `expand` slot shows details under a row, open it with `toggleExpanded(row)` from a cell:

```blade
<x-data-table.column field="actions" :sortable="false">
    <button x-on:click="toggleExpanded(row)">Graphs</button>
</x-data-table.column>
<x-slot:expand>
    <template x-for="graph in row.graphs"><div x-html="graph"></div></template>
</x-slot:expand>
```

Cells can call the table: `request(key, method, url, data)` (toasts errors, `isPending(key)` while running),
`reload()`, `removeRow(row)` after a delete, `notify(message)`. Toolbar controls in the `heading` slot can change
`config.params` and call `changed(true)` to load the first page with them.

A table can send its visible columns (`send-columns`), so the endpoint only does expensive work for shown columns:
`in_array('dnsname', $request->array('columns'), true)`.

## Reloading

```js
window.dispatchEvent(new CustomEvent('data-table:reload', {detail: {name: 'eventlog'}}));
```
