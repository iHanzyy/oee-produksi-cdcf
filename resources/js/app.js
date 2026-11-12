import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('productionControl', (config) => ({
    session: config.initial.data,
    isController: config.initial.isController,
    clockOffsetMs: config.initial?.serverNow ? Date.parse(config.initial.serverNow) - Date.now() : 0,
    areas: config.areas,
    categories: config.categories,
    personnels: config.personnels,
    areaId: '',
    operatorName: '',
    productName: '',
    productId: '',
    batchNumber: '',
    machineName: '',
    machineId: '',
    productMenuOpen: false,
    machineMenuOpen: false,
    categoryId: '',
    personnelId: '',
    replacedComponent: '',
    repairAction: '',
    totalOutput: '',
    rejectOutput: '',
    showDowntimeForm: false,
    formError: '',
    successBanner: '',
    confirmAction: '',
    confirmTitle: '',
    confirmMessage: '',
    confirmButtonText: 'Lanjutkan',
    confirmReady: false,
    alertTitle: 'Pemberitahuan Sistem',
    alertDialogMessage: '',
    elapsed: '00:00:00',
    downtimeElapsed: '00:00:00',
    busy: false,
    busyMessage: 'Menyimpan perubahan...',
    error: '',
    cooldown: false,
    lastActionTime: 0,
    pollTimer: null,
    clockTimer: null,

    init() {
        this.updateElapsed();
        this.clockTimer = window.setInterval(() => this.updateElapsed(), 1000);
        this.pollTimer = window.setInterval(() => this.poll(), 5000);
    },

    destroy() {
        window.clearInterval(this.clockTimer);
        window.clearInterval(this.pollTimer);
    },

    nowMs() {
        return Date.now() + this.clockOffsetMs;
    },

    get state() {
        return this.session?.status ?? 'ready';
    },

    get outputUnitLabel() {
        return this.session?.oee?.outputUnit ?? 'unit';
    },

    get outputStep() {
        return this.outputUnitLabel === 'kg' ? '0.001' : '1';
    },

    get goodOutput() {
        const total = Number(this.totalOutput);
        const reject = Number(this.rejectOutput);
        if (String(this.totalOutput).trim() === '' || String(this.rejectOutput).trim() === '') return null;
        if (!Number.isFinite(total) || !Number.isFinite(reject) || reject > total) return null;
        return total - reject;
    },

    get selectedArea() {
        return this.areas.find((area) => area.id === Number(this.areaId));
    },

    get products() {
        return this.selectedArea?.products ?? [];
    },

    get machines() {
        return this.selectedArea?.machines ?? [];
    },

    get filteredProducts() {
        if (!this.productName || !this.productName.trim()) return this.products;
        const query = this.productName.trim().toLowerCase();
        return this.products.filter((p) => p.name.toLowerCase().includes(query));
    },

    get filteredMachines() {
        if (!this.machineName || !this.machineName.trim()) return this.machines;
        const query = this.machineName.trim().toLowerCase();
        return this.machines.filter((m) => m.name.toLowerCase().includes(query));
    },

    changeArea() {
        this.productName = '';
        this.productId = '';
        this.machineName = '';
        this.machineId = '';
        this.productMenuOpen = false;
        this.machineMenuOpen = false;
        this.error = '';
    },

    matchProduct() {
        const query = (this.productName || '').trim().toLowerCase();
        const found = this.products.find((p) => p.name.toLowerCase() === query);
        this.productId = found ? found.id : '';
    },

    matchMachine() {
        const query = (this.machineName || '').trim().toLowerCase();
        const found = this.machines.find((m) => m.name.toLowerCase() === query);
        this.machineId = found ? found.id : '';
    },

    selectProduct(product) {
        this.productName = product.name;
        this.productId = product.id;
        this.productMenuOpen = false;
        this.error = '';
    },

    selectMachine(machine) {
        this.machineName = machine.name;
        this.machineId = machine.id;
        this.machineMenuOpen = false;
        this.error = '';
    },

    showAlert(message, title = 'Pemberitahuan Sistem') {
        this.alertTitle = title;
        this.alertDialogMessage = message;
    },

    async start() {
        if (this.busy || this.cooldown) return;

        this.matchProduct();
        this.matchMachine();

        if (!this.productId || !this.machineId) {
            this.error = 'Pilih produk dan mesin dari daftar yang tersedia.';
            return;
        }

        this.cooldown = true;
        this.lastActionTime = Date.now();

        const succeeded = await this.mutate(config.urls.start, {
            area_id: Number(this.areaId),
            operator_name: this.operatorName,
            product_id: this.productId,
            batch_number: this.batchNumber,
            machine_id: this.machineId,
        }, 'Menyiapkan sesi produksi...');

        if (succeeded) {
            this.successBanner = 'Sesi produksi dimulai. Mesin siap dipantau.';
            window.setTimeout(() => {
                this.successBanner = '';
            }, 3000);
            window.setTimeout(() => {
                this.cooldown = false;
            }, 3000);
        } else {
            this.cooldown = false;
        }
    },

    async takeover(sessionId) {
        await this.mutate(
            `${config.urls.base}/${sessionId}/takeover`,
            {},
            'Mengalihkan kontrol ke tablet ini...'
        );
    },

    askStop() {
        if (this.cooldown || this.busy || (Date.now() - this.lastActionTime < 3000)) return;
        this.confirmTitle = 'Konfirmasi STOP MESIN';
        this.confirmMessage = 'Hentikan mesin dan mulai mencatat downtime kendala untuk mesin ini?';
        this.confirmButtonText = 'Ya, STOP MESIN';
        this.confirmAction = 'stop';
        this.confirmReady = false;
        window.setTimeout(() => {
            this.confirmReady = true;
        }, 800);
    },

    async stop() {
        this.lastActionTime = Date.now();
        const succeeded = await this.mutate(
            `${config.urls.base}/${this.session.id}/stop`,
            {},
            'Menghentikan mesin...'
        );
        if (succeeded) {
            this.showDowntimeForm = false;
            this.formError = '';
            this.showAlert(
                'Mesin sedang downtime. Setelah perbaikan selesai, tekan Selesai Downtime lalu isi form kendala.',
                'Pemberitahuan Mesin'
            );
        }
    },

    askFinish() {
        if (this.cooldown || this.busy || (Date.now() - this.lastActionTime < 3000)) return;
        this.totalOutput = '';
        this.rejectOutput = '';
        this.confirmTitle = 'Konfirmasi Selesai Produksi';
        this.confirmMessage = 'Pastikan proses produksi sudah tuntas, lalu isi total output dan reject.';
        this.confirmButtonText = 'Selesaikan Produksi';
        this.confirmAction = 'finish';
        this.confirmReady = false;
        window.setTimeout(() => {
            this.confirmReady = true;
        }, 800);
    },

    canConfirm() {
        if (!this.confirmReady) return false;
        if (this.confirmAction !== 'finish') return true;
        const total = String(this.totalOutput ?? '').trim();
        const reject = String(this.rejectOutput ?? '').trim();
        return total !== ''
            && reject !== ''
            && Number.isFinite(Number(total))
            && Number.isFinite(Number(reject))
            && Number(total) >= 0
            && Number(reject) >= 0
            && Number(reject) <= Number(total);
    },

    cancelConfirm() {
        this.confirmAction = '';
        this.confirmTitle = '';
        this.confirmMessage = '';
        this.confirmReady = false;
    },

    async executeConfirm() {
        if (!this.canConfirm() || this.cooldown || this.busy) return;
        if (!this.confirmAction) return;
        const action = this.confirmAction;
        this.cancelConfirm();

        if (action === 'stop') await this.stop();
        if (action === 'finish') await this.finish();
    },

    openDowntimeForm() {
        this.showDowntimeForm = true;
        this.formError = '';
    },

    closeDowntimeForm() {
        this.showDowntimeForm = false;
        this.formError = '';
    },

    async resume() {
        this.formError = '';

        if (!this.categoryId) {
            this.formError = 'Pilih kategori kendala dulu.';
            return;
        }

        if (!this.personnelId) {
            this.formError = 'Pilih pelaksana perbaikan dulu.';
            return;
        }

        if (!this.repairAction || !this.repairAction.trim()) {
            this.formError = 'Isi tindakan perbaikan yang sudah dilakukan.';
            return;
        }

        this.cooldown = true;
        this.lastActionTime = Date.now();

        const succeeded = await this.mutate(`${config.urls.base}/${this.session.id}/resume`, {
            category_id: Number(this.categoryId),
            personnel_id: Number(this.personnelId),
            replaced_component: this.replacedComponent,
            repair_action: this.repairAction,
        }, 'Menyimpan kendala dan menjalankan mesin...');

        if (succeeded) {
            this.showDowntimeForm = false;
            this.categoryId = '';
            this.personnelId = '';
            this.replacedComponent = '';
            this.repairAction = '';
            this.formError = '';
            this.successBanner = 'Mesin kembali berjalan.';
            window.setTimeout(() => {
                this.successBanner = '';
            }, 3000);
            window.setTimeout(() => {
                this.cooldown = false;
            }, 3000);
        } else {
            this.cooldown = false;
        }
    },

    async finish() {
        if (
            this.cooldown
            || this.busy
            || this.session?.status !== 'running'
            || this.nowMs() - Date.parse(this.session.startTime) < 3000
        ) return;

        await this.mutate(`${config.urls.base}/${this.session.id}/complete-confirmed`, {
            confirmed: true,
            total_output: Number(this.totalOutput),
            reject_output: Number(this.rejectOutput),
        }, 'Menyelesaikan sesi produksi...');
    },

    async acknowledge() {
        if (await this.mutate(
            `${config.urls.base}/${this.session.id}/acknowledge`,
            {},
            'Menyiapkan sesi baru...'
        )) {
            this.cancelConfirm();
            this.areaId = '';
            this.operatorName = '';
            this.batchNumber = '';
            this.changeArea();
        }
    },

    async poll() {
        if (this.busy || this.confirmAction || this.showDowntimeForm || this.productMenuOpen || this.machineMenuOpen) {
            return;
        }

        if (typeof document !== 'undefined' && document.hidden) {
            return;
        }

        const query = this.session?.id ? `?session_id=${this.session.id}` : '';

        try {
            const response = await fetch(`${config.urls.status}${query}`, {
                headers: { Accept: 'application/json' },
            });
            if (response.status === 401) {
                window.location.reload();
                return;
            }
            if (!response.ok) return;
            this.apply(await response.json());
        } catch {
            // Heartbeat failure: abaikan dan coba lagi di interval berikutnya
        }
    },

    async mutate(url, body = {}, busyMessage = 'Menyimpan perubahan...') {
        this.busy = true;
        this.busyMessage = busyMessage;
        this.error = '';

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(body),
            });

            let payload = null;
            try {
                payload = await response.json();
            } catch {
                payload = null;
            }

            if (!response.ok) {
                if (response.status === 401) {
                    window.location.reload();
                    return false;
                }
                const firstFieldError = payload?.errors
                    ? Object.values(payload.errors).flat()[0]
                    : null;
                const message = firstFieldError
                    || payload?.message
                    || (response.status === 419
                        ? 'Halaman sudah terlalu lama dibuka. Muat ulang halaman lalu coba lagi.'
                        : 'Permintaan belum bisa diproses. Coba lagi.');
                this.error = message;
                this.formError = message;
                this.showAlert(message, 'Pemberitahuan Sistem');
                return false;
            }

            if (payload) {
                this.apply(payload);
            }
            return true;
        } catch {
            const err = 'Koneksi ke server terputus. Periksa jaringan Wi-Fi tablet lalu coba lagi.';
            this.error = err;
            this.formError = err;
            this.showAlert(err, 'Gangguan Jaringan');
            return false;
        } finally {
            this.busy = false;
        }
    },

    apply(payload) {
        if (payload?.serverNow) {
            this.clockOffsetMs = Date.parse(payload.serverNow) - Date.now();
        }

        if (this.isController !== payload.isController) {
            this.isController = payload.isController;
        }

        const currentId = this.session?.id ?? null;
        const newId = payload.data?.id ?? null;
        const currentStatus = this.session?.status ?? null;
        const newStatus = payload.data?.status ?? null;
        const currentDowntimeId = this.session?.downtime?.id ?? null;
        const newDowntimeId = payload.data?.downtime?.id ?? null;

        const sessionChanged = currentId !== newId
            || currentStatus !== newStatus
            || currentDowntimeId !== newDowntimeId;

        if (sessionChanged) {
            this.session = payload.data;
            this.updateElapsed();
        } else if (this.session && payload.data && payload.data.status === 'completed') {
            this.session.summary = payload.data.summary;
            this.session.oee = payload.data.oee;
            this.session.endTime = payload.data.endTime;
        }
    },

    updateElapsed() {
        if (!this.session?.startTime) {
            this.elapsed = '00:00:00';
            this.downtimeElapsed = '00:00:00';
            return;
        }

        const now = this.nowMs();
        const end = this.session.endTime ? Date.parse(this.session.endTime) : now;
        const totalSeconds = Math.max(0, Math.floor((end - Date.parse(this.session.startTime)) / 1000));
        const previousDowntimeSeconds = Number(this.session.summary?.totalDowntimeSeconds ?? 0);
        let activeDowntimeSeconds = 0;

        if (this.session.status === 'downtime' && this.session.downtime?.stopTime) {
            activeDowntimeSeconds = Math.max(0, Math.floor((now - Date.parse(this.session.downtime.stopTime)) / 1000));
            this.downtimeElapsed = this.formatSeconds(previousDowntimeSeconds + activeDowntimeSeconds);
        } else {
            this.downtimeElapsed = '00:00:00';
        }

        this.elapsed = this.formatSeconds(Math.max(0, totalSeconds - previousDowntimeSeconds - activeDowntimeSeconds));
    },

    formatSeconds(seconds) {
        const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const remainder = String(seconds % 60).padStart(2, '0');
        return `${hours}:${minutes}:${remainder}`;
    },

    duration(seconds) {
        const value = Number(seconds ?? 0);
        const hours = Math.floor(value / 3600);
        const minutes = Math.floor((value % 3600) / 60);
        return hours > 0 ? `${hours} jam ${minutes} menit` : `${minutes} menit`;
    },

    formatOutput(value) {
        if (value === null || value === undefined || value === '') return '-';
        return `${Number(value).toLocaleString('id-ID', { maximumFractionDigits: 3 })} ${this.outputUnitLabel}`;
    },

    formatPercent(value) {
        if (value === null || value === undefined || value === '') return '-';
        return `${Number(value).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
    },
}));

Alpine.data('historyMonitoring', (config) => ({
    sessions: config.initial || [],
    url: config.url,
    clockOffsetMs: config.serverNow ? Date.parse(config.serverNow) - Date.now() : 0,
    clockTimer: null,
    pollTimer: null,
    now: config.serverNow ? Date.parse(config.serverNow) : Date.now(),

    init() {
        this.clockTimer = window.setInterval(() => {
            this.now = Date.now() + this.clockOffsetMs;
        }, 1000);
        this.pollTimer = window.setInterval(() => this.poll(), 5000);
    },

    destroy() {
        window.clearInterval(this.clockTimer);
        window.clearInterval(this.pollTimer);
    },

    get runningCount() {
        return this.sessions.filter((s) => s.status === 'running').length;
    },

    get downtimeCount() {
        return this.sessions.filter((s) => s.status === 'downtime').length;
    },

    calcElapsed(isoTime) {
        if (!isoTime) return '00:00:00';
        const seconds = Math.max(0, Math.floor((this.now - Date.parse(isoTime)) / 1000));
        const hours = String(Math.floor(seconds / 3600)).padStart(2, '0');
        const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0');
        const remainder = String(seconds % 60).padStart(2, '0');
        return `${hours}:${minutes}:${remainder}`;
    },

    async poll() {
        if (typeof document !== 'undefined' && document.hidden) return;
        try {
            const res = await fetch(this.url, {
                headers: { Accept: 'application/json' },
            });
            if (!res.ok) return;
            const data = await res.json();
            if (data.serverNow) {
                this.clockOffsetMs = Date.parse(data.serverNow) - Date.now();
                this.now = Date.now() + this.clockOffsetMs;
            }
            if (data.sessions) {
                this.sessions = data.sessions;
            }
        } catch {
            // Heartbeat failure: ignore
        }
    },
}));

Alpine.start();
