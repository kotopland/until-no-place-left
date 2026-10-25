// Nagoya Oikos Map Controller for Alpine.js & Leaflet
window.nagoyaMap = function(config) {
    return {
        map: null,
        geoJsonLayer: null,
        churchesLayer: null,
        oikosLayer: null,
        spotsLayer: null,
        prayerWalksLayer: null,

        // Reactive layer visibility states bound with entangle
        showHeatmap: config.showHeatmap ?? true,
        showChurches: config.showChurches ?? true,
        showOikosLines: config.showOikosLines ?? true,
        showMercySpots: config.showMercySpots ?? true,
        showEventPins: config.showEventPins ?? true,
        showPrayerWalks: config.showPrayerWalks ?? true,

        wardsGeoJson: config.wardsGeoJson || null,
        churches: config.churches || [],
        lines: config.lines || [],
        specialSpots: config.specialSpots || [],
        prayerWalks: config.prayerWalks || [],

        toggleLayer(layer) {
            if (layer === 'heatmap') this.showHeatmap = !this.showHeatmap;
            else if (layer === 'churches') this.showChurches = !this.showChurches;
            else if (layer === 'oikos') this.showOikosLines = !this.showOikosLines;
            else if (layer === 'mercy') this.showMercySpots = !this.showMercySpots;
            else if (layer === 'events') this.showEventPins = !this.showEventPins;
            else if (layer === 'prayer_walks') this.showPrayerWalks = !this.showPrayerWalks;

            this.renderLayers();
        },

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
            if (this.geoJsonLayer) {
                this.map.removeLayer(this.geoJsonLayer);
                this.geoJsonLayer = null;
            }

            if (this.showHeatmap && this.wardsGeoJson) {
                this.geoJsonLayer = L.geoJSON(this.wardsGeoJson, {
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
                            <div class="min-w-[190px] p-2.5 rounded-xl bg-slate-900 text-slate-100 border border-slate-700 shadow-2xl">
                                <div class="font-bold text-white text-xs">${feature.properties.name} (${feature.properties.name_ja})</div>
                                <div class="text-[11px] text-emerald-400 font-semibold mt-0.5">${feature.properties.openness}% Spiritual Openness</div>
                                <div class="text-[10px] text-slate-400 mt-1">${feature.properties.npcs_count} Discovered Residents</div>
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
            if (this.oikosLayer) {
                this.map.removeLayer(this.oikosLayer);
                this.oikosLayer = null;
            }

            if (this.showOikosLines && this.lines) {
                this.oikosLayer = L.layerGroup();
                this.lines.forEach(line => {
                    let strokeColor = '#38bdf8'; // friend cyan
                    if (line.type === 'family') strokeColor = '#f43f5e'; // rose
                    else if (line.type === 'coworker') strokeColor = '#a855f7'; // purple
                    else if (line.type === 'neighbor') strokeColor = '#06b6d4'; // bright cyan

                    const poly = L.polyline(line.coords, {
                        color: strokeColor,
                        weight: 2.5,
                        opacity: 0.75,
                        dashArray: '4 4'
                    });

                    poly.bindTooltip(`
                        <div class="min-w-[200px] p-2.5 rounded-xl bg-slate-900 text-slate-100 border border-slate-700 shadow-2xl">
                            <div class="font-bold text-cyan-400 text-xs">${line.type_label}</div>
                            <div class="text-[11px] text-white mt-0.5 font-semibold">${line.from_name} ➔ ${line.to_name}</div>
                            <div class="text-[10px] text-amber-400 font-bold mt-1">Relational Trust: ${line.trust}%</div>
                        </div>
                    `, { sticky: true });

                    poly.addTo(this.oikosLayer);
                });
                this.oikosLayer.addTo(this.map);
            }

            // 3. Churches Layer
            if (this.churchesLayer) {
                this.map.removeLayer(this.churchesLayer);
                this.churchesLayer = null;
            }

            if (this.showChurches && this.churches) {
                this.churchesLayer = L.layerGroup();
                this.churches.forEach(church => {
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
                        <div class="min-w-[200px] p-2.5 rounded-xl bg-slate-900 text-slate-100 border border-slate-700 shadow-2xl">
                            <div class="font-bold text-white text-xs">${church.name} (${church.badge})</div>
                            <div class="text-[11px] text-slate-300 mt-0.5">Leader: <span class="font-semibold text-white">${church.leader}</span></div>
                            <div class="text-[10px] text-emerald-400 font-semibold mt-1">Acts 2 Health: ${church.health_score}%</div>
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
            if (this.spotsLayer) {
                this.map.removeLayer(this.spotsLayer);
                this.spotsLayer = null;
            }

            if ((this.showMercySpots || this.showEventPins) && this.specialSpots) {
                this.spotsLayer = L.layerGroup();
                this.specialSpots.forEach(spot => {
                    if (spot.type === 'mercy' && !this.showMercySpots) return;
                    if (spot.type === 'event' && !this.showEventPins) return;

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
                        <div class="max-w-[220px] p-2.5 rounded-xl bg-slate-900 text-slate-100 border border-slate-700 shadow-2xl">
                            <div class="font-bold text-white text-xs">${spot.name}</div>
                            <div class="text-[11px] text-slate-400 mt-0.5">${spot.ward}</div>
                            <div class="text-[10px] text-slate-300 mt-1 leading-snug">${spot.desc}</div>
                        </div>
                    `);

                    marker.addTo(this.spotsLayer);
                });
                this.spotsLayer.addTo(this.map);
            }

            // 5. Prayer Walks Street Polylines Layer (Uniform Amber / Gold System)
            if (this.prayerWalksLayer) {
                this.map.removeLayer(this.prayerWalksLayer);
                this.prayerWalksLayer = null;
            }

            if (this.showPrayerWalks && this.prayerWalks && this.prayerWalks.length > 0) {
                this.prayerWalksLayer = L.layerGroup();
                this.prayerWalks.forEach(walk => {
                    if (!walk.coords || walk.coords.length === 0) return;

                    // Uniform Amber / Gold Palette matching the Amber filter button and legend
                    const glowColor = '#f59e0b'; // Amber 500
                    const coreColor = '#fbbf24'; // Amber 400

                    // Luminous street glow line
                    const glowLine = L.polyline(walk.coords, {
                        color: glowColor,
                        weight: walk.pop_discovered ? 8 : 6,
                        opacity: walk.pop_discovered ? 0.55 : 0.40,
                        lineCap: 'round',
                        lineJoin: 'round'
                    });

                    // Core street path line (solid when Person of Peace is found, dashed when interceding)
                    const coreLine = L.polyline(walk.coords, {
                        color: coreColor,
                        weight: walk.pop_discovered ? 4 : 3,
                        opacity: 1.0,
                        dashArray: walk.pop_discovered ? null : '6 6',
                        lineCap: 'round',
                        lineJoin: 'round'
                    });

                    const routeTitle = walk.route_name || 'Neighborhood Prayer Route';
                    const walkerLabel = walk.walker_name || 'Missionary';
                    const popBadge = walk.pop_discovered
                        ? `<div class="mt-1.5 px-2 py-0.5 rounded-lg bg-amber-950 text-amber-300 border border-amber-600/80 text-[10px] font-bold flex items-center gap-1 shadow-sm">
                            <span>🕊️</span>
                            <span>Person of Peace: ${walk.discovered_npc || 'Receptive Seeker'}</span>
                           </div>`
                        : `<div class="mt-1.5 text-[10px] text-amber-300 font-medium flex items-center gap-1">
                            <span>✨</span>
                            <span>Spiritual Ground Softened & Mapped</span>
                           </div>`;

                    const tooltipHtml = `
                        <div class="min-w-[220px] p-2.5 rounded-xl bg-slate-900 text-slate-100 border border-slate-700 shadow-2xl">
                            <div class="flex items-center gap-1.5 font-bold text-white text-xs leading-snug">
                                <span class="text-amber-400">🚶</span>
                                <span>${routeTitle}</span>
                            </div>
                            <div class="text-[11px] text-amber-400 font-semibold mt-0.5">${walk.ward_name} (${walk.ward_name_ja}) • Day ${walk.day}, M${walk.month}</div>
                            <div class="text-[10px] text-slate-300 mt-1 flex items-center gap-1">
                                <span class="text-slate-400">Walker:</span>
                                <span class="font-bold text-white">${walkerLabel}</span>
                            </div>
                            ${popBadge}
                        </div>
                    `;

                    glowLine.bindTooltip(tooltipHtml, { sticky: true });
                    coreLine.bindTooltip(tooltipHtml, { sticky: true });

                    glowLine.addTo(this.prayerWalksLayer);
                    coreLine.addTo(this.prayerWalksLayer);

                    // Start waypoint marker in matching amber gold
                    const startPt = walk.coords[0];
                    const startIcon = L.divIcon({
                        className: 'prayer-walk-marker',
                        html: `
                            <div style="background-color: #f59e0b; width: 24px; height: 24px; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 12px; box-shadow: 0 0 12px #f59e0b, 0 0 2px #fff; border: 2px solid #ffffff;">
                                ${walk.pop_discovered ? '🕊️' : '🚶'}
                            </div>
                        `,
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
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

// Digital Countdown Clock Controller for 10-Minute Day Ticks
window.digitalClock = function(initialSeconds, nextTickIso, canAutoTick) {
    return {
        secondsLeft: Math.max(0, initialSeconds || 600),
        nextTickTarget: nextTickIso ? new Date(nextTickIso).getTime() : (Date.now() + (initialSeconds || 600) * 1000),
        canAutoTick: !!canAutoTick,
        timer: null,

        init() {
            this.recalcSeconds();
            this.timer = setInterval(() => {
                this.tick();
            }, 1000);
        },

        recalcSeconds() {
            if (this.nextTickTarget) {
                const diff = Math.floor((this.nextTickTarget - Date.now()) / 1000);
                this.secondsLeft = Math.max(0, diff);
            }
        },

        tick() {
            this.recalcSeconds();

            if (!this.canAutoTick) return;

            if (this.secondsLeft <= 0) {
                // Countdown reached 00:00! Trigger automatic day tick
                this.secondsLeft = 600;
                this.nextTickTarget = Date.now() + 600000;
                if (this.$wire && typeof this.$wire.advanceDay === 'function') {
                    this.$wire.advanceDay(false);
                }
            }
        },

        get minutes() {
            return String(Math.floor(this.secondsLeft / 60)).padStart(2, '0');
        },

        get seconds() {
            return String(this.secondsLeft % 60).padStart(2, '0');
        },

        destroy() {
            if (this.timer) clearInterval(this.timer);
        }
    };
};

// Procedural Uplifting Japanese Koto & Luminous Ambient Pad Soundscape (Web Audio API)
class NagoyaSoundscape {
    constructor() {
        this.ctx = null;
        this.masterGain = null;
        this.droneOscs = [];
        this.timer = null;
        this.isPlaying = false;
        // Bright & uplifting D Major / Yo Pentatonic scale (D, E, F#, A, B)
        this.notes = [
            146.83, // D3
            185.00, // F#3
            220.00, // A3
            246.94, // B3
            293.66, // D4
            329.63, // E4
            369.99, // F#4
            440.00, // A4
            493.88, // B4
            587.33, // D5
            659.25, // E5
            739.99, // F#5
            880.00, // A5
            987.77, // B5
            1174.66 // D6 (Sparkling chime)
        ];

        // Uplifting ascending phrases (arpeggios)
        this.phrases = [
            [293.66, 369.99, 440.00, 587.33], // D - F# - A - D (Sunrise triumph)
            [220.00, 293.66, 369.99, 440.00], // A - D - F# - A (Warm harvest)
            [369.99, 440.00, 493.88, 587.33], // F# - A - B - D (Joyful momentum)
            [440.00, 587.33, 739.99, 880.00], // A - D - F# - A (High bell cascade)
            [293.66, 440.00, 587.33, 739.99], // D - A - D - F# (Faithful horizon)
        ];
    }

    init() {
        if (!this.ctx) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            this.ctx = new AudioCtx();

            this.masterGain = this.ctx.createGain();
            this.masterGain.gain.setValueAtTime(0.0001, this.ctx.currentTime);
            this.masterGain.connect(this.ctx.destination);

            // Shimmering celestial echo delay
            this.delay = this.ctx.createDelay();
            this.delay.delayTime.value = 0.42;
            this.feedback = this.ctx.createGain();
            this.feedback.gain.value = 0.36;
            this.delay.connect(this.feedback);
            this.feedback.connect(this.delay);
            this.delay.connect(this.masterGain);
        }
    }

    startDrone() {
        // Warm, luminous major chord pad: D3 (146.83), A3 (220.00), D4 (293.66), F#4 (369.99)
        const chordFreqs = [146.83, 220.00, 293.66, 369.99];
        this.droneOscs = [];

        chordFreqs.forEach((freq, idx) => {
            const osc = this.ctx.createOscillator();
            const filter = this.ctx.createBiquadFilter();
            const gain = this.ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, this.ctx.currentTime);

            // Gentle warm lowpass filter - lets warm harmonics through without mud
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(540 + idx * 120, this.ctx.currentTime);

            // Soft atmospheric volume
            gain.gain.setValueAtTime(0.035 / (idx * 0.4 + 1), this.ctx.currentTime);

            osc.connect(filter);
            filter.connect(gain);
            gain.connect(this.masterGain);
            gain.connect(this.delay);

            osc.start();
            this.droneOscs.push(osc);
        });
    }

    pluckKotoNote(freq, velocity = 0.16) {
        if (!this.ctx || !this.isPlaying) return;

        const now = this.ctx.currentTime;
        const osc = this.ctx.createOscillator();
        const harmonicOsc = this.ctx.createOscillator();
        const filter = this.ctx.createBiquadFilter();
        const noteGain = this.ctx.createGain();

        // Warm plucked string with gentle octave shimmer
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(freq, now);

        harmonicOsc.type = 'sine';
        harmonicOsc.frequency.setValueAtTime(freq * 2, now);

        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(Math.min(freq * 3.5, 4500), now);
        filter.frequency.exponentialRampToValueAtTime(Math.max(freq * 1.2, 300), now + 2.5);

        noteGain.gain.setValueAtTime(0.0001, now);
        noteGain.gain.linearRampToValueAtTime(velocity, now + 0.012);
        noteGain.gain.exponentialRampToValueAtTime(0.0001, now + 2.8);

        osc.connect(filter);
        harmonicOsc.connect(filter);
        filter.connect(noteGain);
        noteGain.connect(this.masterGain);
        noteGain.connect(this.delay);

        osc.start(now);
        harmonicOsc.start(now);
        osc.stop(now + 3.0);
        harmonicOsc.stop(now + 3.0);
    }

    playPhrase(phraseNotes) {
        if (!this.isPlaying) return;
        phraseNotes.forEach((note, index) => {
            setTimeout(() => {
                if (this.isPlaying) {
                    this.pluckKotoNote(note, 0.14 + (index === phraseNotes.length - 1 ? 0.04 : 0));
                }
            }, index * 240);
        });
    }

    scheduleNextNote() {
        if (!this.isPlaying) return;

        // 35% chance to play an uplifting ascending arpeggio phrase, otherwise melodic plucked note
        if (Math.random() < 0.35) {
            const phrase = this.phrases[Math.floor(Math.random() * this.phrases.length)];
            this.playPhrase(phrase);
            const delayMs = 2800 + Math.random() * 2000;
            this.timer = setTimeout(() => this.scheduleNextNote(), delayMs);
        } else {
            const note = this.notes[Math.floor(Math.random() * this.notes.length)];
            this.pluckKotoNote(note, 0.16);

            // Shimmering companion note accompaniment
            if (Math.random() < 0.45) {
                setTimeout(() => {
                    if (this.isPlaying) {
                        const companion = this.notes[Math.floor(Math.random() * this.notes.length)];
                        this.pluckKotoNote(companion, 0.11);
                    }
                }, 280 + Math.random() * 300);
            }

            const delayMs = 1500 + Math.random() * 2200;
            this.timer = setTimeout(() => this.scheduleNextNote(), delayMs);
        }
    }

    start() {
        this.init();
        if (this.ctx.state === 'suspended') {
            this.ctx.resume();
        }

        this.isPlaying = true;
        this.masterGain.gain.cancelScheduledValues(this.ctx.currentTime);
        this.masterGain.gain.setValueAtTime(this.masterGain.gain.value, this.ctx.currentTime);
        this.masterGain.gain.linearRampToValueAtTime(0.32, this.ctx.currentTime + 1.2);

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
