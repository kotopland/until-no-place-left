// Nagoya Oikos Map Controller for Alpine.js & Leaflet
window.nagoyaMap = function(config) {
    return {
        map: null,
        geoJsonLayer: null,
        churchesLayer: null,
        oikosLayer: null,
        spotsLayer: null,
        prayerWalksLayer: null,

        initMap() {
            if (typeof L === 'undefined') {
                console.error('Leaflet is not loaded yet');
                return;
            }

            const mapEl = document.getElementById('nagoya-leaflet-map');
            if (!mapEl) return;

            // Clear any previous Leaflet ID if navigating back and forth across tabs
            if (mapEl._leaflet_id) {
                mapEl._leaflet_id = null;
            }

            if (this.map) {
                try {
                    this.map.remove();
                } catch (e) {}
                this.map = null;
            }

            // Center on Nagoya City Hall / Sakae coordinates
            this.map = L.map('nagoya-leaflet-map', {
                center: [35.1750, 136.9150],
                zoom: 12,
                zoomControl: false,
                attributionControl: true
            });

            L.control.zoom({ position: 'bottomright' }).addTo(this.map);

            // 100% Free OpenStreetMap Tiles (Zero API Keys, Open Source)
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" class="text-slate-500 hover:underline">OpenStreetMap</a>'
            }).addTo(this.map);

            this.renderLayers();

            // Crucial: invalidateSize after map renders in DOM
            setTimeout(() => {
                if (this.map) {
                    this.map.invalidateSize();
                }
            }, 300);

            this.$watch('showHeatmap', () => this.renderLayers());
            this.$watch('showChurches', () => this.renderLayers());
            this.$watch('showOikosLines', () => this.renderLayers());
            this.$watch('showMercySpots', () => this.renderLayers());
            this.$watch('showEventPins', () => this.renderLayers());
            this.$watch('showPrayerWalks', () => this.renderLayers());
        },

        renderLayers() {
            if (!this.map || typeof L === 'undefined') return;

            // 1. Wards Heatmap Layer
            if (this.geoJsonLayer) this.map.removeLayer(this.geoJsonLayer);

            if (config.showHeatmap && config.wardsGeoJson) {
                this.geoJsonLayer = L.geoJSON(config.wardsGeoJson, {
                    style: (feature) => {
                        const openness = feature.properties.openness;
                        let fillColor = '#64748b'; // slate-500
                        if (openness >= 45) fillColor = '#10b981'; // emerald-500
                        else if (openness >= 30) fillColor = '#f59e0b'; // amber-500
                        else if (openness >= 20) fillColor = '#6366f1'; // indigo-500

                        return {
                            color: '#334155',
                            weight: 1.5,
                            fillColor: fillColor,
                            fillOpacity: 0.22
                        };
                    },
                    onEachFeature: (feature, layer) => {
                        layer.bindTooltip(`
                            <div class="text-xs p-1">
                                <strong>${feature.properties.name} (${feature.properties.name_ja})</strong><br>
                                <span class="text-emerald-400">${feature.properties.openness}% Spiritual Openness</span><br>
                                <span class="text-slate-400">${feature.properties.npcs_count} Discovered Residents</span>
                            </div>
                        `, { sticky: true });

                        layer.on('click', () => {
                            if (window.Livewire) {
                                window.Livewire.dispatch('map-ward-selected', { wardId: feature.properties.id });
                            }
                        });
                    }
                }).addTo(this.map);
            }

            // 2. Oikos Relational Lines (Arcs)
            if (this.oikosLayer) this.map.removeLayer(this.oikosLayer);

            if (config.showOikosLines && config.lines) {
                this.oikosLayer = L.layerGroup();
                config.lines.forEach(line => {
                    let strokeColor = '#38bdf8'; // friend cyan
                    if (line.type === 'family') strokeColor = '#f43f5e'; // rose
                    else if (line.type === 'coworker') strokeColor = '#a855f7'; // purple
                    else if (line.type === 'neighbor') strokeColor = '#10b981'; // emerald

                    const poly = L.polyline(line.coords, {
                        color: strokeColor,
                        weight: 2,
                        opacity: 0.65,
                        dashArray: '4 4'
                    });

                    poly.bindTooltip(`
                        <div class="text-xs p-1">
                            <strong>${line.type_label}</strong><br>
                            ${line.from_name} ➔ ${line.to_name}<br>
                            <span class="text-amber-400 font-bold">Trust: ${line.trust}%</span>
                        </div>
                    `);

                    poly.addTo(this.oikosLayer);
                });
                this.oikosLayer.addTo(this.map);
            }

            // 3. Churches Layer
            if (this.churchesLayer) this.map.removeLayer(this.churchesLayer);

            if (config.showChurches && config.churches) {
                this.churchesLayer = L.layerGroup();
                config.churches.forEach(church => {
                    const icon = L.divIcon({
                        className: 'custom-church-pin',
                        html: `
                            <div style="background-color: ${church.color}; width: 34px; height: 34px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 11px; color: #fff; box-shadow: 0 0 15px ${church.color}; border: 2px solid #ffffff;">
                                ${church.badge}
                            </div>
                        `,
                        iconSize: [34, 34],
                        iconAnchor: [17, 17]
                    });

                    const marker = L.marker([church.lat, church.lng], { icon });
                    marker.bindTooltip(`
                        <div class="text-xs p-1">
                            <strong>${church.name}</strong> (${church.badge})<br>
                            Leader: ${church.leader}<br>
                            <span class="text-emerald-400">Health: ${church.health_score}%</span>
                        </div>
                    `);

                    marker.on('click', () => {
                        if (window.Livewire) {
                            window.Livewire.dispatch('map-church-selected', { churchId: church.id });
                        }
                    });

                    marker.addTo(this.churchesLayer);
                });
                this.churchesLayer.addTo(this.map);
            }

            // 4. Special Spots: Mercy Outreaches & Event Platforms
            if (this.spotsLayer) this.map.removeLayer(this.spotsLayer);

            if ((config.showMercySpots || config.showEventPins) && config.specialSpots) {
                this.spotsLayer = L.layerGroup();
                config.specialSpots.forEach(spot => {
                    if (spot.type === 'mercy' && !config.showMercySpots) return;
                    if (spot.type === 'event' && !config.showEventPins) return;

                    const bg = spot.type === 'mercy' ? '#e11d48' : '#7c3aed';
                    const icon = L.divIcon({
                        className: 'custom-spot-pin',
                        html: `
                            <div style="background-color: ${bg}; width: 28px; height: 28px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 14px; box-shadow: 0 0 10px ${bg}; border: 2px solid #fff;">
                                ${spot.icon}
                            </div>
                        `,
                        iconSize: [28, 28],
                        iconAnchor: [14, 14]
                    });

                    const marker = L.marker([spot.lat, spot.lng], { icon });
                    marker.bindTooltip(`
                        <div class="text-xs p-1 max-w-[200px]">
                            <strong>${spot.name}</strong><br>
                            <span class="text-slate-400">${spot.ward}</span><br>
                            <span class="text-slate-300">${spot.desc}</span>
                        </div>
                    `);

                    marker.addTo(this.spotsLayer);
                });
                this.spotsLayer.addTo(this.map);
            }

            // 5. Prayer Walks Street Polylines Layer
            if (this.prayerWalksLayer) this.map.removeLayer(this.prayerWalksLayer);

            if (config.showPrayerWalks && config.prayerWalks && config.prayerWalks.length > 0) {
                this.prayerWalksLayer = L.layerGroup();
                config.prayerWalks.forEach(walk => {
                    if (!walk.coords || walk.coords.length === 0) return;

                    const strokeColor = walk.pop_discovered ? '#10b981' : '#f59e0b';

                    // Luminous street glow line
                    const glowLine = L.polyline(walk.coords, {
                        color: strokeColor,
                        weight: 6,
                        opacity: 0.35,
                        lineCap: 'round',
                        lineJoin: 'round'
                    });

                    // Core street path line
                    const coreLine = L.polyline(walk.coords, {
                        color: walk.pop_discovered ? '#34d399' : '#fbbf24',
                        weight: 3,
                        opacity: 0.95,
                        dashArray: walk.pop_discovered ? null : '5 5',
                        lineCap: 'round',
                        lineJoin: 'round'
                    });

                    const popBadge = walk.pop_discovered
                        ? `<div class="mt-1 px-1.5 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-700 text-[10px] font-bold">🕊️ Person of Peace Discovered: ${walk.discovered_npc || 'Receptive Seeker'}</div>`
                        : `<div class="mt-1 text-[10px] text-amber-300">Spiritual Ground Softened & Mapped</div>`;

                    const tooltipHtml = `
                        <div class="text-xs p-1 min-w-[210px]">
                            <div class="flex items-center gap-1 font-bold text-white">
                                <span>🚶</span>
                                <span>${walk.route_name}</span>
                            </div>
                            <div class="text-[11px] text-slate-400">${walk.ward_name} (${walk.ward_name_ja}) • Day ${walk.day}, M${walk.month}</div>
                            <div class="text-[10px] text-slate-300 mt-0.5">Walker: <span class="font-semibold text-white">${walk.walker_name}</span></div>
                            ${popBadge}
                        </div>
                    `;

                    glowLine.bindTooltip(tooltipHtml, { sticky: true });
                    coreLine.bindTooltip(tooltipHtml, { sticky: true });

                    glowLine.addTo(this.prayerWalksLayer);
                    coreLine.addTo(this.prayerWalksLayer);

                    // Start waypoint marker
                    const startPt = walk.coords[0];
                    const startIcon = L.divIcon({
                        className: 'prayer-walk-marker',
                        html: `
                            <div style="background-color: ${strokeColor}; width: 22px; height: 22px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 11px; box-shadow: 0 0 10px ${strokeColor}; border: 2px solid #ffffff;">
                                ${walk.pop_discovered ? '🕊️' : '🚶'}
                            </div>
                        `,
                        iconSize: [22, 22],
                        iconAnchor: [11, 11]
                    });
                    const startMarker = L.marker(startPt, { icon: startIcon });
                    startMarker.bindTooltip(tooltipHtml, { sticky: true });
                    startMarker.addTo(this.prayerWalksLayer);
                });
                this.prayerWalksLayer.addTo(this.map);
            }
        }
    };
};

// Procedural Japanese Koto & Meditative Pad Soundscape (Web Audio API)
class NagoyaSoundscape {
    constructor() {
        this.ctx = null;
        this.masterGain = null;
        this.droneOscs = [];
        this.timer = null;
        this.isPlaying = false;
        // Insen & Hirajoshi pentatonic scale frequencies in D
        this.notes = [146.83, 220.00, 293.66, 311.13, 392.00, 440.00, 523.25, 587.33, 622.25, 783.99, 880.00];
    }

    init() {
        if (!this.ctx) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            this.ctx = new AudioCtx();

            this.masterGain = this.ctx.createGain();
            this.masterGain.gain.setValueAtTime(0.0001, this.ctx.currentTime);
            this.masterGain.connect(this.ctx.destination);

            // Spacious temple reverb delay
            this.delay = this.ctx.createDelay();
            this.delay.delayTime.value = 0.38;
            this.feedback = this.ctx.createGain();
            this.feedback.gain.value = 0.42;
            this.delay.connect(this.feedback);
            this.feedback.connect(this.delay);
            this.delay.connect(this.masterGain);
        }
    }

    startDrone() {
        const droneFreqs = [73.42, 110.0, 146.83];
        this.droneOscs = [];

        droneFreqs.forEach((freq, idx) => {
            const osc = this.ctx.createOscillator();
            const filter = this.ctx.createBiquadFilter();
            const gain = this.ctx.createGain();

            osc.type = idx === 0 ? 'sine' : 'triangle';
            osc.frequency.setValueAtTime(freq, this.ctx.currentTime);

            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(320 + idx * 80, this.ctx.currentTime);

            gain.gain.setValueAtTime(0.06 / (idx + 1), this.ctx.currentTime);

            osc.connect(filter);
            filter.connect(gain);
            gain.connect(this.masterGain);
            gain.connect(this.delay);

            osc.start();
            this.droneOscs.push(osc);
        });
    }

    pluckKotoNote(freq) {
        if (!this.ctx || !this.isPlaying) return;

        const now = this.ctx.currentTime;
        const osc = this.ctx.createOscillator();
        const filter = this.ctx.createBiquadFilter();
        const noteGain = this.ctx.createGain();

        osc.type = 'triangle';
        osc.frequency.setValueAtTime(freq, now);

        filter.type = 'bandpass';
        filter.frequency.setValueAtTime(freq * 1.5, now);
        filter.Q.setValueAtTime(4.2, now);

        noteGain.gain.setValueAtTime(0.0001, now);
        noteGain.gain.linearRampToValueAtTime(0.18, now + 0.015);
        noteGain.gain.exponentialRampToValueAtTime(0.0001, now + 3.2);

        osc.connect(filter);
        filter.connect(noteGain);
        noteGain.connect(this.masterGain);
        noteGain.connect(this.delay);

        osc.start(now);
        osc.stop(now + 3.5);
    }

    scheduleNextNote() {
        if (!this.isPlaying) return;

        const note = this.notes[Math.floor(Math.random() * this.notes.length)];
        this.pluckKotoNote(note);

        if (Math.random() < 0.35) {
            setTimeout(() => {
                if (this.isPlaying) {
                    const harmonic = this.notes[Math.floor(Math.random() * this.notes.length)];
                    this.pluckKotoNote(harmonic);
                }
            }, 300 + Math.random() * 400);
        }

        const delayMs = 1800 + Math.random() * 2400;
        this.timer = setTimeout(() => this.scheduleNextNote(), delayMs);
    }

    start() {
        this.init();
        if (this.ctx.state === 'suspended') {
            this.ctx.resume();
        }

        this.isPlaying = true;
        this.masterGain.gain.cancelScheduledValues(this.ctx.currentTime);
        this.masterGain.gain.setValueAtTime(this.masterGain.gain.value, this.ctx.currentTime);
        this.masterGain.gain.linearRampToValueAtTime(0.35, this.ctx.currentTime + 1.2);

        this.startDrone();
        this.scheduleNextNote();
    }

    stop() {
        if (!this.ctx || !this.isPlaying) return;

        this.isPlaying = false;
        clearTimeout(this.timer);

        const now = this.ctx.currentTime;
        this.masterGain.gain.cancelScheduledValues(now);
        this.masterGain.gain.setValueAtTime(this.masterGain.gain.value, now);
        this.masterGain.gain.linearRampToValueAtTime(0.0001, now + 1.0);

        setTimeout(() => {
            this.droneOscs.forEach(osc => {
                try { osc.stop(); } catch(e) {}
            });
            this.droneOscs = [];
        }, 1100);
    }
}

const nagoyaSoundscape = new NagoyaSoundscape();

function setupAlpineAudioStore() {
    if (window.Alpine && !window.Alpine.store('audio')) {
        window.Alpine.store('audio', {
            isPlaying: false,

            init() {
                const saved = localStorage.getItem('nagoya_music_enabled');
                if (saved === 'true') {
                    const autoStart = () => {
                        if (!this.isPlaying && localStorage.getItem('nagoya_music_enabled') === 'true') {
                            this.play();
                        }
                        window.removeEventListener('click', autoStart);
                        window.removeEventListener('keydown', autoStart);
                    };
                    window.addEventListener('click', autoStart, { once: true });
                    window.addEventListener('keydown', autoStart, { once: true });
                }
            },

            toggle() {
                if (this.isPlaying) {
                    this.pause();
                } else {
                    this.play();
                }
            },

            play() {
                nagoyaSoundscape.start();
                this.isPlaying = true;
                localStorage.setItem('nagoya_music_enabled', 'true');
            },

            pause() {
                nagoyaSoundscape.stop();
                this.isPlaying = false;
                localStorage.setItem('nagoya_music_enabled', 'false');
            }
        });
    }
}

if (window.Alpine) {
    window.Alpine.data('nagoyaMap', window.nagoyaMap);
    setupAlpineAudioStore();
}

document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.data('nagoyaMap', window.nagoyaMap);
        setupAlpineAudioStore();
    }
});
