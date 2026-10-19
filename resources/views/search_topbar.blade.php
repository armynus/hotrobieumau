@include('shared.jquery-ui')

<style>
/* ========================================================
   AUTOCOMPLETE DROPDOWN & SEARCH ENHANCEMENTS
   ======================================================== */
.ui-autocomplete {
    max-height: 420px;
    overflow-y: auto;
    background: #ffffff !important;
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 14px !important;
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.14), 0 4px 12px rgba(15, 23, 42, 0.04) !important;
    padding: 6px !important;
    z-index: 1060 !important;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.ui-autocomplete::-webkit-scrollbar {
    width: 6px;
}

.ui-autocomplete::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.ui-autocomplete::-webkit-scrollbar-track {
    background: transparent;
}

/* Menu Item */
.ui-menu .ui-menu-item {
    list-style: none !important;
    padding: 0 !important;
    margin: 0 0 3px 0 !important;
    border: none !important;
}

.ui-menu .ui-menu-item:last-child {
    margin-bottom: 0 !important;
}

/* Menu Item Wrapper (Text & Icon) */
.ui-menu .ui-menu-item-wrapper {
    display: flex !important;
    align-items: center !important;
    gap: 10px !important;
    width: 100% !important;
    margin: 0 !important; /* Ghi đè triệt để margin: -1px của jquery-ui */
    padding: 9px 12px !important;
    border-radius: 9px !important;
    color: #1e293b !important;
    font-size: 0.88rem !important;
    font-weight: 500 !important;
    border: 1px solid transparent !important;
    background: transparent !important;
    transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease !important;
    cursor: pointer !important;
    line-height: 1.4 !important;
    box-sizing: border-box !important;
}

.ui-menu .ui-menu-item-text {
    flex: 1 1 auto !important;
    min-width: 0 !important;
    font-size: 0.88rem !important;
    font-weight: 500 !important;
    color: inherit !important;
    line-height: 1.4 !important;
}

/* Icon (Word doc / form icon) */
.ui-menu-item-wrapper svg,
.ui-menu-item-wrapper i {
    width: 18px !important;
    height: 18px !important;
    color: #a51c3e !important;
    flex-shrink: 0 !important;
    transition: color 0.15s ease !important;
}

/* Hover & Active Keyboard Navigation State (Agribank Brand Style) */
/* QUAN TRỌNG: Giữ nguyên font-weight 500 và margin 0 để tuyệt đối không làm giãn chữ gây nhảy dòng */
.ui-menu .ui-menu-item-wrapper.ui-state-focus,
.ui-menu .ui-menu-item-wrapper.ui-state-active,
.ui-menu .ui-menu-item:hover .ui-menu-item-wrapper {
    margin: 0 !important;
    background: #fff0f3 !important;
    border: 1px solid #fecdd3 !important;
    color: #a51c3e !important;
    font-weight: 500 !important;
}

.ui-menu .ui-menu-item-wrapper.ui-state-focus .ui-menu-item-text,
.ui-menu .ui-menu-item-wrapper.ui-state-active .ui-menu-item-text,
.ui-menu .ui-menu-item:hover .ui-menu-item-text {
    font-weight: 500 !important;
    color: #a51c3e !important;
}

.ui-menu .ui-menu-item-wrapper.ui-state-focus svg,
.ui-menu .ui-menu-item-wrapper.ui-state-focus i,
.ui-menu .ui-menu-item-wrapper.ui-state-active svg,
.ui-menu .ui-menu-item-wrapper.ui-state-active i,
.ui-menu .ui-menu-item:hover svg,
.ui-menu .ui-menu-item:hover i {
    color: #a51c3e !important;
}

/* Search Status Pill (Zero Layout Shift) */
.form-search-status {
    position: absolute !important;
    top: calc(100% + 6px) !important;
    left: 12px !important;
    z-index: 1055 !important;
    display: inline-flex !important;
    align-items: center !important;
    padding: 5px 12px !important;
    background: #1e293b !important;
    color: #f8fafc !important;
    font-size: 0.78rem !important;
    font-weight: 500 !important;
    border-radius: 9999px !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.18) !important;
    white-space: nowrap !important;
    pointer-events: none !important;
    line-height: 1.3 !important;
}

.form-search-status:empty {
    display: none !important;
}
</style>

<span id="form-search-config" hidden data-config="{{ json_encode(["url" => route("support_form.search")]) }}"></span>
<script src="{{ asset('js/user/form-search.js') }}?v={{ filemtime(public_path('js/user/form-search.js')) }}"></script>