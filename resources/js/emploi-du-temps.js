/**
 * EmploiDuTemps — Calendrier custom style Google Agenda
 * Vues : mois, semaine, jour
 * 100% Tailwind + Vanilla JS
 */

class EmploiDuTemps {
    constructor(container, options = {}) {
        this.container = typeof container === 'string' ? document.getElementById(container) : container;
        if (!this.container) return;

        this.endpoint = options.endpoint || '';
        this.currentView = options.view || 'week';
        this.currentDate = options.date ? new Date(options.date) : new Date();
        this.events = [];
        this.onEventClick = options.onEventClick || null;
        this.onBlockClick = options.onBlockClick || null;
        this.entrepriseColors = options.entrepriseColors || {};
        this.showGoogleBanner = options.showGoogleBanner || false;
        this.googleConnected = options.googleConnected || false;
        this.googleConnectUrl = options.googleConnectUrl || '';
        this.csrfToken = options.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.syncUrl = options.syncUrl || '';
        this.loading = false;
        this.syncing = false;
        this.hourStart = 0;   // Journée complète depuis minuit
        this.hourEnd = 24;    // Jusqu'à minuit
        this.pixelsPerHour = 60;

        this.joursSemaine = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        this.joursComplets = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
        this.mois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

        this.render();
        this.fetchEvents();
    }

    // === NAVIGATION ===

    navigate(direction) {
        const d = this.currentDate;
        switch (this.currentView) {
            case 'month':
                d.setMonth(d.getMonth() + direction);
                break;
            case 'week':
                d.setDate(d.getDate() + (direction * 7));
                break;
            case 'day':
                d.setDate(d.getDate() + direction);
                break;
        }
        this.fetchEvents();
    }

    goToday() {
        this.currentDate = new Date();
        this.fetchEvents();
    }

    setView(view) {
        this.currentView = view;
        this.render();
        this.fetchEvents();
    }

    // === DATA ===

    async fetchEvents() {
        if (!this.endpoint || this.loading) return;
        this.loading = true;
        this.showLoading();

        const { start, end } = this.getDateRange();
        const url = `${this.endpoint}?start=${this.formatDateISO(start)}&end=${this.formatDateISO(end)}`;

        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            this.events = data.events || [];
        } catch (e) {
            console.error('EmploiDuTemps: erreur chargement', e);
            this.events = [];
        }

        this.loading = false;
        this.renderContent();
    }

    async forceSync() {
        if (!this.syncUrl || this.syncing) return;
        this.syncing = true;

        const syncBtn = this.headerEl?.querySelector('.edt-sync-btn');
        const syncIcon = this.headerEl?.querySelector('.edt-sync-icon');
        const syncLabel = this.headerEl?.querySelector('.edt-sync-label');

        if (syncIcon) syncIcon.classList.add('animate-spin');
        if (syncLabel) syncLabel.textContent = 'Sync...';
        if (syncBtn) syncBtn.disabled = true;

        try {
            const response = await fetch(this.syncUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': this.csrfToken,
                }
            });
            const data = await response.json();

            if (data.success) {
                if (syncLabel) syncLabel.textContent = 'Synchronisé !';
                if (syncBtn) syncBtn.classList.remove('text-emerald-600', 'dark:text-emerald-400', 'border-emerald-200', 'dark:border-emerald-700');
                if (syncBtn) syncBtn.classList.add('text-green-600', 'dark:text-green-400', 'border-green-300', 'dark:border-green-600');
                // Rafraîchir les événements du calendrier
                await this.fetchEvents();
            } else {
                if (syncLabel) syncLabel.textContent = 'Erreur';
                if (syncBtn) syncBtn.classList.remove('text-emerald-600', 'dark:text-emerald-400');
                if (syncBtn) syncBtn.classList.add('text-red-600', 'dark:text-red-400');
            }
        } catch (e) {
            console.error('EmploiDuTemps: erreur sync', e);
            if (syncLabel) syncLabel.textContent = 'Erreur';
        }

        if (syncIcon) syncIcon.classList.remove('animate-spin');
        if (syncBtn) syncBtn.disabled = false;
        this.syncing = false;

        // Remettre le texte original après 3 secondes
        setTimeout(() => {
            if (syncLabel) syncLabel.textContent = 'Sync Google';
            if (syncBtn) {
                syncBtn.classList.remove('text-green-600', 'dark:text-green-400', 'border-green-300', 'dark:border-green-600', 'text-red-600', 'dark:text-red-400');
                syncBtn.classList.add('text-emerald-600', 'dark:text-emerald-400', 'border-emerald-200', 'dark:border-emerald-700');
            }
        }, 3000);
    }

    getDateRange() {
        const d = new Date(this.currentDate);
        let start, end;

        switch (this.currentView) {
            case 'month': {
                start = new Date(d.getFullYear(), d.getMonth(), 1);
                end = new Date(d.getFullYear(), d.getMonth() + 1, 0);
                // Étendre pour remplir la grille
                start.setDate(start.getDate() - start.getDay());
                end.setDate(end.getDate() + (6 - end.getDay()));
                break;
            }
            case 'week': {
                start = new Date(d);
                const dayOfWeek = start.getDay();
                const diffToMonday = dayOfWeek === 0 ? -6 : 1 - dayOfWeek;
                start.setDate(start.getDate() + diffToMonday);
                end = new Date(start);
                end.setDate(start.getDate() + 6);
                break;
            }
            case 'day': {
                start = new Date(d);
                end = new Date(d);
                break;
            }
        }

        start.setHours(0, 0, 0, 0);
        end.setHours(23, 59, 59, 999);
        return { start, end };
    }

    // === RENDU PRINCIPAL ===

    render() {
        this.container.innerHTML = '';

        // Header
        const header = this.createHeader();
        this.container.appendChild(header);

        // Google banner
        if (this.showGoogleBanner && !this.googleConnected) {
            const banner = this.createGoogleBanner();
            this.container.appendChild(banner);
        }

        // Content area
        this.contentArea = document.createElement('div');
        this.contentArea.id = 'edt-content';
        this.container.appendChild(this.contentArea);
    }

    createHeader() {
        const header = document.createElement('div');
        header.className = 'bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 mb-4 overflow-hidden';

        // Top bar with gradient
        const topBar = document.createElement('div');
        topBar.className = 'bg-gradient-to-r from-indigo-600 to-purple-500 px-4 sm:px-6 py-4';
        topBar.innerHTML = `
            <div class="flex items-center justify-between">
                <button type="button" class="edt-nav-prev p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </button>
                <div class="text-center">
                    <h2 class="edt-title text-lg sm:text-xl font-bold text-white">Chargement...</h2>
                    <p class="edt-subtitle text-xs sm:text-sm text-white/80"></p>
                </div>
                <button type="button" class="edt-nav-next p-2 rounded-lg bg-white/20 hover:bg-white/30 text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            </div>
        `;

        // Controls bar
        const controls = document.createElement('div');
        controls.className = 'px-4 sm:px-6 py-3 bg-slate-50 dark:bg-slate-700/50 border-b border-slate-200 dark:border-slate-700 flex flex-wrap items-center justify-between gap-3';

        const syncBtnHtml = this.googleConnected && this.syncUrl
            ? `<button type="button" class="edt-sync-btn inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 rounded-lg transition-colors border border-emerald-200 dark:border-emerald-700" title="Synchroniser Google Agenda">
                    <svg class="w-4 h-4 edt-sync-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span class="edt-sync-label hidden sm:inline">Sync Google</span>
               </button>`
            : '';

        controls.innerHTML = `
            <div class="flex items-center gap-2">
                <button type="button" class="edt-today-btn px-3 py-1.5 text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 rounded-lg transition-colors border border-indigo-200 dark:border-indigo-700">
                    Aujourd'hui
                </button>
                ${syncBtnHtml}
            </div>
            <div class="flex rounded-lg overflow-hidden border border-slate-200 dark:border-slate-600">
                <button type="button" class="edt-view-btn px-3 py-1.5 text-xs sm:text-sm font-medium transition-colors" data-view="month">Mois</button>
                <button type="button" class="edt-view-btn px-3 py-1.5 text-xs sm:text-sm font-medium transition-colors" data-view="week">Semaine</button>
                <button type="button" class="edt-view-btn px-3 py-1.5 text-xs sm:text-sm font-medium transition-colors" data-view="day">Jour</button>
            </div>
        `;

        header.appendChild(topBar);
        header.appendChild(controls);

        // Events
        header.querySelector('.edt-nav-prev').addEventListener('click', () => this.navigate(-1));
        header.querySelector('.edt-nav-next').addEventListener('click', () => this.navigate(1));
        header.querySelector('.edt-today-btn').addEventListener('click', () => this.goToday());

        header.querySelectorAll('.edt-view-btn').forEach(btn => {
            btn.addEventListener('click', () => this.setView(btn.dataset.view));
        });

        const syncBtn = header.querySelector('.edt-sync-btn');
        if (syncBtn) {
            syncBtn.addEventListener('click', () => this.forceSync());
        }

        this.headerEl = header;
        this.updateHeaderState();
        return header;
    }

    updateHeaderState() {
        if (!this.headerEl) return;

        // Update view buttons
        this.headerEl.querySelectorAll('.edt-view-btn').forEach(btn => {
            if (btn.dataset.view === this.currentView) {
                btn.className = 'edt-view-btn px-3 py-1.5 text-xs sm:text-sm font-medium transition-colors bg-indigo-600 text-white';
            } else {
                btn.className = 'edt-view-btn px-3 py-1.5 text-xs sm:text-sm font-medium transition-colors bg-white dark:bg-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-600';
            }
        });

        // Update title
        this.updateTitle();
    }

    updateTitle() {
        if (!this.headerEl) return;
        const titleEl = this.headerEl.querySelector('.edt-title');
        const subtitleEl = this.headerEl.querySelector('.edt-subtitle');
        const d = this.currentDate;
        const today = new Date();

        switch (this.currentView) {
            case 'month':
                titleEl.textContent = `${this.mois[d.getMonth()]} ${d.getFullYear()}`;
                subtitleEl.textContent = d.getMonth() === today.getMonth() && d.getFullYear() === today.getFullYear() ? 'Ce mois' : '';
                break;
            case 'week': {
                const { start, end } = this.getDateRange();
                if (start.getMonth() === end.getMonth()) {
                    titleEl.textContent = `${start.getDate()} - ${end.getDate()} ${this.mois[start.getMonth()]}`;
                } else {
                    titleEl.textContent = `${start.getDate()} ${this.mois[start.getMonth()]} - ${end.getDate()} ${this.mois[end.getMonth()]}`;
                }
                const todayStr = this.formatDateISO(today);
                const startStr = this.formatDateISO(start);
                const endStr = this.formatDateISO(end);
                subtitleEl.textContent = (todayStr >= startStr && todayStr <= endStr) ? 'Cette semaine' : '';
                break;
            }
            case 'day':
                titleEl.textContent = `${this.joursComplets[d.getDay()]} ${d.getDate()} ${this.mois[d.getMonth()]}`;
                subtitleEl.textContent = this.formatDateISO(d) === this.formatDateISO(today) ? "Aujourd'hui" : '';
                break;
        }
    }

    createGoogleBanner() {
        const banner = document.createElement('div');
        banner.className = 'mb-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl';
        banner.innerHTML = `
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                    <p class="text-sm text-indigo-800 dark:text-indigo-300">
                        Connectez votre Google Agenda pour voir vos événements personnels et pouvoir bloquer des créneaux facilement.
                    </p>
                </div>
                <a href="${this.googleConnectUrl}" class="flex-shrink-0 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                    Connecter
                </a>
            </div>
        `;
        return banner;
    }

    showLoading() {
        if (this.contentArea) {
            this.contentArea.innerHTML = `
                <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 p-12">
                    <div class="flex items-center justify-center gap-3 text-slate-500 dark:text-slate-400">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span class="text-sm">Chargement de l'emploi du temps...</span>
                    </div>
                </div>
            `;
        }
    }

    // === RENDU DU CONTENU ===

    renderContent() {
        this.updateHeaderState();

        switch (this.currentView) {
            case 'month': this.renderMonth(); break;
            case 'week':  this.renderWeek();  break;
            case 'day':   this.renderDay();   break;
        }
    }

    // === VUE MOIS ===

    renderMonth() {
        const d = this.currentDate;
        const firstDay = new Date(d.getFullYear(), d.getMonth(), 1);
        const lastDay = new Date(d.getFullYear(), d.getMonth() + 1, 0);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - startDate.getDay());

        const wrapper = document.createElement('div');
        wrapper.className = 'bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden';

        // Header jours de la semaine
        const headerRow = document.createElement('div');
        headerRow.className = 'grid grid-cols-7 border-b border-slate-200 dark:border-slate-700';
        this.joursSemaine.forEach(j => {
            const cell = document.createElement('div');
            cell.className = 'py-2 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase';
            cell.textContent = j;
            headerRow.appendChild(cell);
        });
        wrapper.appendChild(headerRow);

        // Grille des jours
        const grid = document.createElement('div');
        grid.className = 'grid grid-cols-7';

        const today = this.formatDateISO(new Date());
        const currentMonth = d.getMonth();
        let currentDate = new Date(startDate);

        for (let i = 0; i < 42; i++) {
            const dateStr = this.formatDateISO(currentDate);
            const isOtherMonth = currentDate.getMonth() !== currentMonth;
            const isToday = dateStr === today;
            const dayEvents = this.getEventsForDay(dateStr);

            const cell = document.createElement('div');
            cell.className = `edt-month-cell ${isToday ? 'edt-month-cell-today' : ''} ${isOtherMonth ? 'edt-month-cell-other' : ''}`;
            cell.dataset.date = dateStr;

            // Numéro du jour
            const dayNum = document.createElement('div');
            dayNum.className = `text-xs font-semibold mb-1 ${isToday ? 'text-green-600 dark:text-green-400' : 'text-slate-700 dark:text-slate-300'}`;
            if (isToday) {
                dayNum.innerHTML = `<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-600 text-white text-xs">${currentDate.getDate()}</span>`;
            } else {
                dayNum.textContent = currentDate.getDate();
            }
            cell.appendChild(dayNum);

            // Événements (max 3 visibles)
            const maxVisible = 3;
            dayEvents.slice(0, maxVisible).forEach(evt => {
                const evtEl = this.createMonthEvent(evt);
                cell.appendChild(evtEl);
            });

            if (dayEvents.length > maxVisible) {
                const more = document.createElement('div');
                more.className = 'edt-month-more';
                more.textContent = `+${dayEvents.length - maxVisible} de plus`;
                more.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.currentDate = new Date(dateStr + 'T12:00:00');
                    this.setView('day');
                });
                cell.appendChild(more);
            }

            // Clic sur le jour → vue jour
            cell.addEventListener('click', () => {
                this.currentDate = new Date(dateStr + 'T12:00:00');
                this.setView('day');
            });

            grid.appendChild(cell);
            currentDate.setDate(currentDate.getDate() + 1);
        }

        wrapper.appendChild(grid);
        this.contentArea.innerHTML = '';
        this.contentArea.appendChild(wrapper);
    }

    createMonthEvent(evt) {
        const el = document.createElement('div');
        let cssClass = 'edt-month-event ';
        if (evt.type === 'google') {
            cssClass += 'edt-event-google';
        } else if (evt.type === 'other_business') {
            cssClass += 'edt-event-other_business';
        } else {
            cssClass += `edt-event-${evt.status || 'en_attente'}`;
        }
        el.className = cssClass;

        const time = evt.start ? new Date(evt.start).toTimeString().substring(0, 5) : '';
        el.textContent = `${time} ${evt.title}`;

        el.addEventListener('click', (e) => {
            e.stopPropagation();
            this.handleEventClick(evt);
        });

        return el;
    }

    // === VUE SEMAINE ===

    renderWeek() {
        const { start } = this.getDateRange();
        const days = [];
        for (let i = 0; i < 7; i++) {
            const d = new Date(start);
            d.setDate(start.getDate() + i);
            days.push(d);
        }
        this.renderTimeGrid(days);
    }

    // === VUE JOUR ===

    renderDay() {
        this.renderTimeGrid([new Date(this.currentDate)]);
    }

    // === GRILLE HORAIRE (semaine + jour) ===

    renderTimeGrid(days) {
        const wrapper = document.createElement('div');
        wrapper.className = 'bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-200 dark:border-slate-700 overflow-hidden';

        const today = this.formatDateISO(new Date());
        const now = new Date();
        const colCount = days.length;

        // Header des jours
        const headerGrid = document.createElement('div');
        headerGrid.className = 'flex border-b border-slate-200 dark:border-slate-700';

        // Espace pour l'axe des heures
        const headerSpacer = document.createElement('div');
        headerSpacer.className = 'edt-time-axis flex-shrink-0';
        headerGrid.appendChild(headerSpacer);

        const headerCols = document.createElement('div');
        headerCols.className = 'flex-1 grid';
        headerCols.style.gridTemplateColumns = `repeat(${colCount}, 1fr)`;

        days.forEach(d => {
            const dateStr = this.formatDateISO(d);
            const isToday = dateStr === today;
            const cell = document.createElement('div');
            cell.className = `edt-header-cell ${isToday ? 'edt-header-cell-today' : ''}`;
            cell.innerHTML = `
                <div class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">${this.joursSemaine[d.getDay()]}</div>
                <div class="text-lg font-bold ${isToday ? 'text-green-600 dark:text-green-400' : 'text-slate-900 dark:text-white'}">${d.getDate()}</div>
            `;
            cell.style.cursor = 'pointer';
            cell.addEventListener('click', () => {
                if (this.currentView === 'week') {
                    this.currentDate = new Date(dateStr + 'T12:00:00');
                    this.setView('day');
                }
            });
            headerCols.appendChild(cell);
        });

        headerGrid.appendChild(headerCols);
        wrapper.appendChild(headerGrid);

        // Conteneur scrollable
        const scrollContainer = document.createElement('div');
        scrollContainer.className = 'edt-scroll-container';

        const gridBody = document.createElement('div');
        gridBody.className = 'flex relative';

        // Axe des heures
        const timeAxis = document.createElement('div');
        timeAxis.className = 'edt-time-axis flex-shrink-0';
        timeAxis.style.height = `${(this.hourEnd - this.hourStart) * this.pixelsPerHour}px`;

        for (let h = this.hourStart; h < this.hourEnd; h++) {
            const label = document.createElement('div');
            label.className = 'edt-time-label';
            label.textContent = `${String(h).padStart(2, '0')}:00`;
            timeAxis.appendChild(label);
        }
        gridBody.appendChild(timeAxis);

        // Colonnes des jours
        const daysContainer = document.createElement('div');
        daysContainer.className = 'flex-1 grid relative';
        daysContainer.style.gridTemplateColumns = `repeat(${colCount}, 1fr)`;

        days.forEach(d => {
            const dateStr = this.formatDateISO(d);
            const isToday = dateStr === today;
            const dayEvents = this.getEventsForDay(dateStr);

            const column = document.createElement('div');
            column.className = 'edt-day-column relative';

            // Lignes horizontales des heures
            for (let h = this.hourStart; h < this.hourEnd; h++) {
                const line = document.createElement('div');
                line.className = 'edt-hour-line';
                line.style.top = `${(h - this.hourStart) * this.pixelsPerHour}px`;
                column.appendChild(line);

                // Demi-heure
                const halfLine = document.createElement('div');
                halfLine.className = 'edt-hour-line edt-hour-line-half';
                halfLine.style.top = `${(h - this.hourStart) * this.pixelsPerHour + this.pixelsPerHour / 2}px`;
                column.appendChild(halfLine);
            }

            // Indicateur "maintenant"
            if (isToday) {
                const nowMinutes = now.getHours() * 60 + now.getMinutes();
                const startMinutes = this.hourStart * 60;
                if (nowMinutes >= startMinutes && nowMinutes <= this.hourEnd * 60) {
                    const indicator = document.createElement('div');
                    indicator.className = 'edt-now-indicator';
                    indicator.style.top = `${((nowMinutes - startMinutes) / 60) * this.pixelsPerHour}px`;
                    column.appendChild(indicator);
                }
            }

            // Événements
            this.renderDayEvents(column, dayEvents);

            column.style.height = `${(this.hourEnd - this.hourStart) * this.pixelsPerHour}px`;
            daysContainer.appendChild(column);
        });

        gridBody.appendChild(daysContainer);
        scrollContainer.appendChild(gridBody);
        wrapper.appendChild(scrollContainer);

        this.contentArea.innerHTML = '';
        this.contentArea.appendChild(wrapper);

        // Scroller vers l'heure actuelle - 1h, ou 7h si avant
        const targetHour = Math.max(7, Math.min(now.getHours() - 1, this.hourEnd - 2));
        scrollContainer.scrollTop = (targetHour - this.hourStart) * this.pixelsPerHour;
    }

    renderDayEvents(column, events) {
        // Calculer les chevauchements pour positionner côte à côte
        const positioned = this.calculateOverlaps(events);

        positioned.forEach(({ event, column: col, totalColumns }) => {
            const el = this.createTimeEvent(event, col, totalColumns);
            if (el) column.appendChild(el);
        });
    }

    calculateOverlaps(events) {
        if (!events.length) return [];

        // Trier par heure de début
        const sorted = events.map(e => ({
            event: e,
            startMin: this.getMinutesFromDate(e.start),
            endMin: this.getMinutesFromDate(e.end || e.start)
        })).sort((a, b) => a.startMin - b.startMin || a.endMin - b.endMin);

        // Assigner les colonnes
        const result = [];
        const columns = [];

        sorted.forEach(item => {
            // Trouver la première colonne libre
            let col = 0;
            while (columns[col] && columns[col] > item.startMin) {
                col++;
            }
            columns[col] = item.endMin;
            result.push({ ...item, column: col });
        });

        // Calculer le nombre total de colonnes pour chaque groupe
        const maxCol = Math.max(...result.map(r => r.column)) + 1;
        return result.map(r => ({
            event: r.event,
            column: r.column,
            totalColumns: maxCol
        }));
    }

    createTimeEvent(event, col, totalColumns) {
        const startMin = this.getMinutesFromDate(event.start);
        const endMin = this.getMinutesFromDate(event.end || event.start);
        const startOffset = this.hourStart * 60;

        if (endMin <= startOffset || startMin >= this.hourEnd * 60) return null;

        const top = Math.max(0, (startMin - startOffset) / 60) * this.pixelsPerHour;
        const height = Math.max(20, ((endMin - Math.max(startMin, startOffset)) / 60) * this.pixelsPerHour);
        const width = 100 / totalColumns;
        const left = col * width;

        const el = document.createElement('div');

        let cssClass = 'edt-event ';
        if (event.type === 'google') {
            cssClass += 'edt-event-google';
        } else if (event.type === 'other_business') {
            cssClass += 'edt-event-other_business';
        } else {
            cssClass += `edt-event-${event.status || 'en_attente'}`;
        }
        el.className = cssClass;

        el.style.top = `${top}px`;
        el.style.height = `${height}px`;
        el.style.left = `calc(${left}% + 2px)`;
        el.style.width = `calc(${width}% - 4px)`;

        const startTime = new Date(event.start).toTimeString().substring(0, 5);
        const endTime = event.end ? new Date(event.end).toTimeString().substring(0, 5) : '';

        let content = `<div class="edt-event-time">${startTime}${endTime ? ' - ' + endTime : ''}</div>`;
        content += `<div class="edt-event-title">${this.escapeHtml(event.title)}</div>`;

        if (event.type === 'google') {
            content += `<div class="edt-event-meta">Google Calendar</div>`;
        } else if (event.type === 'other_business' && event.entreprise_name) {
            content += `<div class="edt-event-meta">${this.escapeHtml(event.entreprise_name)}</div>`;
        } else if (event.client_name) {
            content += `<div class="edt-event-meta">${this.escapeHtml(event.client_name)}</div>`;
        }

        el.innerHTML = content;

        el.addEventListener('click', (e) => {
            e.stopPropagation();
            this.handleEventClick(event);
        });

        return el;
    }

    // === EVENT HANDLERS ===

    handleEventClick(event) {
        if (event.type === 'google' || event.type === 'other_business') {
            if (this.onBlockClick) {
                this.onBlockClick(event);
            }
        } else {
            if (this.onEventClick) {
                this.onEventClick(event);
            }
        }
    }

    // === UTILITAIRES ===

    getEventsForDay(dateStr) {
        return this.events.filter(e => {
            const evtDate = this.formatDateISO(new Date(e.start));
            return evtDate === dateStr;
        }).sort((a, b) => new Date(a.start) - new Date(b.start));
    }

    getMinutesFromDate(dateStr) {
        if (!dateStr) return 0;
        const d = new Date(dateStr);
        return d.getHours() * 60 + d.getMinutes();
    }

    formatDateISO(date) {
        const d = new Date(date);
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// Export global
window.EmploiDuTemps = EmploiDuTemps;
