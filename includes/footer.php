</main>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="fixed top-5 right-5 z-[100] flex flex-col gap-3 pointer-events-none"></div>

<style>
    @keyframes toastIn {
        from { opacity: 0; transform: translateX(40px) scale(0.95); }
        to   { opacity: 1; transform: translateX(0) scale(1); }
    }
    @keyframes toastOut {
        from { opacity: 1; transform: translateX(0) scale(1); }
        to   { opacity: 0; transform: translateX(40px) scale(0.95); }
    }
    .toast-enter { animation: toastIn .35s cubic-bezier(.34,1.56,.64,1) forwards; }
    .toast-leave { animation: toastOut .25s ease forwards; }
</style>

<script>
    lucide.createIcons();

    /* ===== Mobile Sidebar Toggle ===== */
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuBtn = document.getElementById('menuBtn');

    function openSidebar() {
        sidebar.classList.add('open');
        overlay.classList.remove('hidden');
    }
    function closeSidebar() {
        sidebar.classList.remove('open');
        overlay.classList.add('hidden');
    }
    menuBtn?.addEventListener('click', openSidebar);
    overlay?.addEventListener('click', closeSidebar);

    /* ===== Collapsible Sidebar (Desktop) ===== */
    const collapseBtn = document.getElementById('collapseBtn');
    const collapseIcon = document.getElementById('collapseIcon');
    const sidebarTexts = document.querySelectorAll('.sidebar-text');

    function applyCollapse(collapsed) {
        if (collapsed) {
            sidebar.classList.remove('lg:w-64');
            sidebar.classList.add('lg:w-20');
            sidebarTexts.forEach(el => el.classList.add('lg:hidden'));
            collapseIcon?.setAttribute('data-lucide', 'chevrons-right');
        } else {
            sidebar.classList.remove('lg:w-20');
            sidebar.classList.add('lg:w-64');
            sidebarTexts.forEach(el => el.classList.remove('lg:hidden'));
            collapseIcon?.setAttribute('data-lucide', 'chevrons-left');
        }
        lucide.createIcons();
        localStorage.setItem('sidebarCollapsed', collapsed ? '1' : '0');
    }

    collapseBtn?.addEventListener('click', () => {
        const isCollapsed = sidebar.classList.contains('lg:w-20');
        applyCollapse(!isCollapsed);
    });

    document.addEventListener('DOMContentLoaded', () => {
        if (localStorage.getItem('sidebarCollapsed') === '1') {
            applyCollapse(true);
        }
    });

    /* ===== Collapsible Widget Toggle ===== */
    function toggleWidget(id) {
        const el = document.getElementById(id);
        const chevron = document.getElementById(id + 'Chevron');
        el.classList.toggle('hidden');
        chevron.style.transform = el.classList.contains('hidden') ? 'rotate(0deg)' : 'rotate(180deg)';
    }

    /* ===== Countdown Timers (Multiple Events) ===== */
    const countdownEls = document.querySelectorAll('.countdown-mini');
    if (countdownEls.length) {
        function updateCountdowns() {
            countdownEls.forEach(el => {
                const target = new Date(el.dataset.target).getTime();
                const now = new Date().getTime();
                const diff = target - now;

                if (diff <= 0) {
                    el.textContent = 'កំពុងចាប់ផ្តើម!';
                    return;
                }
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

                el.textContent = days > 0
                    ? `${days} ថ្ងៃ ${hours} ម៉ោង`
                    : `${hours} ម៉ោង ${mins} នាទី`;
            });
        }
        updateCountdowns();
        setInterval(updateCountdowns, 60000);
    }

    /* ===== Toast Notification ===== */
    const toastStyles = {
        success: { bg: 'bg-white', border: 'border-green-200', icon: 'check-circle', iconColor: 'text-green-500', bar: 'bg-green-500' },
        error:   { bg: 'bg-white', border: 'border-red-200',   icon: 'x-circle',     iconColor: 'text-red-500',   bar: 'bg-red-500' },
        info:    { bg: 'bg-white', border: 'border-blue-200',  icon: 'info',         iconColor: 'text-blue-500',  bar: 'bg-blue-500' },
    };

    function showToast(message, type = 'success', duration = 4000) {
        const style = toastStyles[type] || toastStyles.info;
        const container = document.getElementById('toastContainer');

        const toast = document.createElement('div');
        toast.className = `toast-enter pointer-events-auto relative overflow-hidden ${style.bg} border ${style.border} shadow-lg rounded-xl px-4 py-3.5 pr-10 flex items-start gap-3 w-80 max-w-[90vw]`;
        toast.innerHTML = `
            <div class="absolute left-0 top-0 bottom-0 w-1 ${style.bar}"></div>
            <i data-lucide="${style.icon}" class="w-5 h-5 ${style.iconColor} flex-shrink-0 mt-0.5"></i>
            <p class="text-sm text-gray-700 leading-snug">${message}</p>
            <button class="absolute top-2.5 right-2.5 text-gray-300 hover:text-gray-500 transition" onclick="dismissToast(this.parentElement)">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        `;
        container.appendChild(toast);
        lucide.createIcons();

        const timer = setTimeout(() => dismissToast(toast), duration);
        toast.dataset.timer = timer;
    }

    function dismissToast(toast) {
        clearTimeout(toast.dataset.timer);
        toast.classList.remove('toast-enter');
        toast.classList.add('toast-leave');
        setTimeout(() => toast.remove(), 250);
    }
</script>
</body>
</html>