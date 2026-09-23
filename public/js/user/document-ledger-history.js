(function (root) {
    'use strict';

    const limit = 15;

    function parts(value, splitLines) {
        if (typeof value !== 'string') return [];
        return (splitLines ? value.split(/\r?\n/) : [value]).map(part => part.trim()).filter(Boolean);
    }

    function identity(value) {
        return value.replace(/\s+/g, ' ').trim().toLocaleLowerCase('vi');
    }

    function unique(values) {
        const seen = new Set();
        return values.filter(function (value) {
            const key = identity(value);
            if (!key || seen.has(key)) return false;
            seen.add(key);
            return true;
        }).slice(0, limit);
    }

    function list(key, splitLines) {
        try {
            const stored = JSON.parse(root.localStorage.getItem(key) || '[]');
            return Array.isArray(stored) ? unique(stored.flatMap(value => parts(value, splitLines))) : [];
        } catch (error) {
            return [];
        }
    }

    function write(key, values) {
        try {
            if (values.length) root.localStorage.setItem(key, JSON.stringify(values));
            else root.localStorage.removeItem(key);
            return true;
        } catch (error) {
            return false;
        }
    }

    function save(key, value, splitLines) {
        const incoming = parts(value, splitLines);
        if (!incoming.length) return;
        write(key, unique(incoming.concat(list(key, splitLines))));
    }

    function remove(key, value, splitLines) {
        write(key, list(key, splitLines).filter(item => identity(item) !== identity(value)));
    }

    root.DocumentLedgerHistory = {list, save, remove, clear: key => write(key, [])};
})(window);
