
<li class="nav-item {{ $isMenuOpen ? 'active' : '' }}">
    <a class="nav-link {{ $isMenuOpen ? '' : 'collapsed' }}"
       href="#" data-toggle="collapse" data-target="#collapseForms"
       aria-expanded="{{ $isMenuOpen ? 'true' : 'false' }}"
       aria-controls="collapseForms">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
             class="bi bi-pass-fill" viewBox="0 0 16 16">
            <path d="M10 0a2 2 0 1 1-4 0H3.5A1.5 1.5 0 0 0 2 1.5v13A1.5 1.5 0 0 0 3.5 16h9a1.5 1.5 0 0 0 1.5-1.5v-13A1.5 1.5 0 0 0 12.5 0zM4.5 5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1 0-1m0 2h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1 0-1"/>
        </svg>
        <span>Hỗ trợ biểu mẫu</span>
    </a>

    <div id="collapseForms"
         class="collapse {{ $isMenuOpen ? 'show' : '' }}"
         data-parent="#accordionSidebar">
        <div class="bg-white py-2 collapse-inner rounded">
            @foreach($formTypes as $formType)
                @php
                    // ✅ Dùng route() thay vì query() vì route là /support_forms/{type}
                    $isActive = $isMenuOpen && request()->route('type') == $formType->id;
                @endphp
                <a class="collapse-item {{ $isActive ? 'active' : '' }}"
                   href="{{ route('support_forms.index', ['type' => $formType->id]) }}">
                    {{ $formType->type_name }}
                </a>
            @endforeach
        </div>
    </div>
</li>