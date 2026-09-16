// Form values and save ordering, independent of jQuery and HTTP.
export function readFields(fields) {
    const values = {};
    for (const field of fields) {
        const name = field.name || field.id;
        if (!name) continue;
        if (field.type === 'checkbox') {
            values[name] ||= [];
            if (field.checked) values[name].push(field.value);
        } else if (field.type === 'radio') {
            if (!(name in values)) values[name] = null;
            if (field.checked) values[name] = field.value;
        } else if (field.multiple) {
            values[name] = Array.from(field.options).filter(option => option.selected).map(option => option.value);
        } else values[name] = field.value;
    }
    return values;
}

export function restoreFields(fields, values) {
    let ambiguousLegacy = false;
    const counts = {};
    fields.forEach(field => { const name = field.name || field.id; counts[name] = (counts[name] || 0) + 1; });
    for (const field of fields) {
        const name = field.name || field.id;
        if (!Object.prototype.hasOwnProperty.call(values, name)) continue;
        const value = values[name];
        if (field.type === 'checkbox' || field.type === 'radio') {
            if (typeof value === 'boolean' || value === 'true' || value === 'false') {
                // Old grouped booleans lost the selected values; never invent them.
                ambiguousLegacy ||= counts[name] > 1;
                field.checked = counts[name] === 1 && (value === true || value === 'true');
            } else field.checked = Array.isArray(value) ? value.includes(field.value) : value === field.value;
        } else if (field.tagName === 'SELECT') {
            const selected = (Array.isArray(value) ? value : [value ?? '']).map(String);
            for (const item of selected) {
                if (item && !Array.from(field.options).some(option => option.value === item)) {
                    const option = field.ownerDocument.createElement('option');
                    option.value = item;
                    option.textContent = item;
                    field.add(option);
                }
            }
            for (const option of Array.from(field.options)) option.selected = selected.includes(option.value);
            if (!field.multiple) field.value = selected[0] ?? '';
        } else field.value = value ?? '';
    }
    return ambiguousLegacy;
}

export class DraftSaveQueue {
    constructor(send, status, persisted) {
        this.send = send;
        this.status = status;
        this.persisted = persisted;
        this.revision = null;
        this.pending = null;
        this.inFlight = null;
        this.blocked = false;
    }
    enqueue(payload) { this.pending = JSON.parse(JSON.stringify(payload)); }
    async flush() {
        if (this.inFlight || this.blocked || this.revision === null || this.pending === null) return;
        const payload = this.pending;
        this.pending = null;
        this.status('saving');
        const operation = Promise.resolve().then(() => this.send(payload, this.revision));
        this.inFlight = operation;
        try {
            const response = await operation;
            this.revision = response.revision;
            this.persisted(payload, this.pending, this.revision);
            this.status(this.pending === null ? 'saved' : 'saving');
        } catch (error) {
            if (this.pending === null) this.pending = payload;
            this.blocked = true;
            this.status(error.status === 409 ? 'conflict' : 'error');
        } finally { this.inFlight = null; }
        if (!this.blocked && this.pending !== null) return this.flush();
    }
}
