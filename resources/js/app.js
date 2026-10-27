// Nagoya Oikos Map Controller for Alpine.js & Leaflet
window.nagoyaMap = function (config) {
    return {
        map: null,
        geoJsonLayer: null,
        churchesLayer: null,
        oikosLayer: null,
        spotsLayer: null,
        prayerWalksLayer: null,
        housesLayer: null,

        // Reactive layer visibility states bound with entangle
        showHeatmap: config.showHeatmap ?? true,
        showChurches: config.showChurches ?? true,
        showOikosLines: config.showOikosLines ?? true,
        showMercySpots: config.showMercySpots ?? true,
        showEventPins: config.showEventPins ?? true,
        showPrayerWalks: config.showPrayerWalks ?? true,
        showHouses: config.showHouses ?? true,

        wardsGeoJson: config.wardsGeoJson || null,
        churches: config.churches || [],
        lines: config.lines || [],
        specialSpots: config.specialSpots || [],
        prayerWalks: config.prayerWalks || [],
        houses: config.houses || [],

        toggleLayer(layer) {
            if (layer === 'heatmap') this.showHeatmap = !this.showHeatmap;
            else if (layer === 'churches') this.showChurches = !this.showChurches;
            else if (layer === 'oikos') this.showOikosLines = !this.showOikosLines;
            else if (layer === 'mercy') this.showMercySpots = !this.showMercySpots;
            else if (layer === 'events') this.showEventPins = !this.showEventPins;
            else if (layer === 'prayer_walks') this.showPrayerWalks = !this.showPrayerWalks;
            else if (layer === 'houses') this.showHouses = !this.showHouses;

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
                } catch (e) { }
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
            this.$watch('showHouses', () => this.renderLayers());
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

            // 6. Missionary Houses Layer (Player & Co-Laborer Residences)
            if (this.housesLayer) {
                this.map.removeLayer(this.housesLayer);
                this.housesLayer = null;
            }

            if (this.showHouses && this.houses && this.houses.length > 0) {
                this.housesLayer = L.layerGroup();
                this.houses.forEach(house => {
                    const isPlayer = house.is_current_player;
                    const bgGradient = isPlayer
                        ? 'linear-gradient(135deg, #10b981, #047857)'
                        : 'linear-gradient(135deg, #6366f1, #4338ca)';
                    const borderColor = isPlayer ? '#fef08a' : '#ffffff';
                    const shadowColor = isPlayer ? 'rgba(16, 185, 129, 0.75)' : 'rgba(99, 102, 241, 0.6)';
                    const size = isPlayer ? 34 : 28;
                    const iconEmoji = isPlayer ? '🏡' : '🏠';

                    const icon = L.divIcon({
                        className: 'custom-house-pin',
                        html: `
                            <div style="background: ${bgGradient}; width: ${size}px; height: ${size}px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: ${isPlayer ? 17 : 14}px; box-shadow: 0 0 12px ${shadowColor}; border: 2px solid ${borderColor}; cursor: pointer; transform: translate(-50%, -50%);">
                                ${iconEmoji}
                            </div>
                        `,
                        iconSize: [size, size],
                        iconAnchor: [size / 2, size / 2]
                    });

                    const marker = L.marker([house.lat, house.lng], { icon });

                    const statusBadge = isPlayer
                        ? `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-950 text-emerald-300 border border-emerald-700">🏡 Your Home Base</span>`
                        : `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-950 text-indigo-300 border border-indigo-700">🏠 Co-Missionary Base</span>`;

                    const tooltipHtml = `
                        <div class="max-w-[240px] p-3 rounded-2xl bg-slate-900 text-slate-100 border border-slate-700 shadow-2xl space-y-1.5">
                            <div class="flex items-center justify-between gap-1">
                                <span class="font-bold text-white text-xs">${house.name}</span>
                                ${statusBadge}
                            </div>
                            <div class="text-[11px] text-slate-300 font-medium">${house.ward_name} (${house.ward_name_ja})</div>
                            <div class="text-[10px] text-emerald-400 leading-snug">
                                ${isPlayer ? '✨ Local Neighbor Advantage: 0 AP transit • +25% follow-up trust bonus!' : '🤝 Fellow missionary laboring in this harvest field.'}
                            </div>
                            <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-800 flex items-center justify-between">
                                <span>Day ${house.day}</span>
                                <span class="text-amber-400 font-semibold">${house.score} pts</span>
                            </div>
                        </div>
                    `;

                    marker.bindTooltip(tooltipHtml, {
                        permanent: false,
                        direction: 'top',
                        offset: [0, -14],
                        className: 'house-tooltip'
                    });

                    marker.addTo(this.housesLayer);
                });
                this.housesLayer.addTo(this.map);
            }
        }
    };
};

// Digital Countdown Clock Controller for 15-Minute Week Ticks
window.digitalClock = function (initialSeconds, nextTickIso, canAutoTick) {
    return {
        secondsLeft: Math.max(0, initialSeconds || 900),
        nextTickTarget: nextTickIso ? new Date(nextTickIso).getTime() : (Date.now() + (initialSeconds || 900) * 1000),
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
                // Countdown reached 00:00! Trigger automatic week tick (15 minutes)
                this.secondsLeft = 900;
                this.nextTickTarget = Date.now() + 900000;
                if (this.$wire && typeof this.$wire.advanceWeek === 'function') {
                    this.$wire.advanceWeek(false);
                } else if (this.$wire && typeof this.$wire.advanceDay === 'function') {
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

// Procedural Japanese Koto & Ambient Soundscape (Web Audio API)
// Features famous iconic Japanese melodies (specifically excluding the national anthem)
const NOTE_FREQS = {
    // Octave 2
    'C2': 65.41, 'C#2': 69.30, 'Db2': 69.30, 'D2': 73.42, 'D#2': 77.78, 'Eb2': 77.78,
    'E2': 82.41, 'F2': 87.31, 'F#2': 92.50, 'Gb2': 92.50, 'G2': 98.00, 'G#2': 103.83, 'Ab2': 103.83,
    'A2': 110.00, 'A#2': 116.54, 'Bb2': 116.54, 'B2': 123.47,
    // Octave 3
    'C3': 130.81, 'C#3': 138.59, 'Db3': 138.59, 'D3': 146.83, 'D#3': 155.56, 'Eb3': 155.56,
    'E3': 164.81, 'F3': 174.61, 'F#3': 185.00, 'Gb3': 185.00, 'G3': 196.00, 'G#3': 207.65, 'Ab3': 207.65,
    'A3': 220.00, 'A#3': 233.08, 'Bb3': 233.08, 'B3': 246.94,
    // Octave 4
    'C4': 261.63, 'C#4': 277.18, 'Db4': 277.18, 'D4': 293.66, 'D#4': 311.13, 'Eb4': 311.13,
    'E4': 329.63, 'F4': 349.23, 'F#4': 369.99, 'Gb4': 369.99, 'G4': 392.00, 'G#4': 415.30, 'Ab4': 415.30,
    'A4': 440.00, 'A#4': 466.16, 'Bb4': 466.16, 'B4': 493.88,
    // Octave 5
    'C5': 523.25, 'C#5': 554.37, 'Db5': 554.37, 'D5': 587.33, 'D#5': 622.25, 'Eb5': 622.25,
    'E5': 659.25, 'F5': 698.46, 'F#5': 739.99, 'Gb5': 739.99, 'G5': 783.99, 'G#5': 830.61, 'Ab5': 830.61,
    'A5': 880.00, 'A#5': 932.33, 'Bb5': 932.33, 'B5': 987.77,
    // Octave 6
    'C6': 1046.50, 'C#6': 1108.73, 'Db6': 1108.73, 'D6': 1174.66, 'D#6': 1244.51, 'Eb6': 1244.51,
    'E6': 1318.51, 'F6': 1396.91, 'F#6': 1479.98, 'Gb6': 1479.98, 'G6': 1567.98, 'G#6': 1661.22,
    'Ab6': 1661.22, 'A6': 1760.00, 'A#6': 1864.66, 'Bb6': 1864.66, 'B6': 1975.53,
    // Octave 7
    'C7': 2093.00
};

// 17 Iconic Japanese Melodies: Traditional, Folk, Modern, and Cinematic classics (excluding the national anthem)
const FAMOUS_JAPANESE_SONGS = [
    // --- Traditional & Folk Melodies ---
    {
        id: 'sakura',
        englishTitle: 'Sakura Sakura',
        japaneseTitle: 'さくら さくら',
        category: 'Traditional & Folk',
        tempo: 72,
        droneChord: ['A2', 'E3', 'A3', 'C4'],
        melody: [
            ['A4', 1], ['A4', 1], ['B4', 2],
            ['A4', 1], ['A4', 1], ['B4', 2],
            ['A4', 1], ['B4', 1], ['C5', 1], ['B4', 1],
            ['A4', 1], ['B4', 0.5], ['A4', 0.5], ['F4', 2],
            ['E4', 1], ['C4', 1], ['E4', 1], ['F4', 1],
            ['E4', 1], ['E4', 0.5], ['C4', 0.5], ['B3', 2],
            ['A4', 1], ['B4', 1], ['C5', 1], ['B4', 1],
            ['A4', 1], ['B4', 1], ['C5', 2],
            ['B4', 1], ['C5', 1], ['B4', 1], ['A4', 1],
            ['F4', 1], ['E4', 1], ['E4', 2],
            ['A4', 1], ['A4', 1], ['B4', 2],
            ['A4', 1], ['A4', 1], ['B4', 2],
            ['A4', 1], ['B4', 1], ['A4', 1], ['F4', 1],
            ['E4', 3], [null, 1]
        ]
    },
    {
        id: 'haru-no-umi',
        englishTitle: 'Haru no Umi (Spring Sea)',
        japaneseTitle: '春の海',
        category: 'Traditional & Folk',
        tempo: 76,
        droneChord: ['D3', 'A3', 'D4', 'F4'],
        melody: [
            ['D4', 1], ['F4', 1], ['A4', 2], ['G4', 1], ['F4', 1], ['D4', 2],
            ['A4', 1], ['D5', 1.5], ['C5', 0.5], ['A4', 2], ['F4', 1], ['G4', 1], ['A4', 3], [null, 1],
            ['D5', 1], ['F5', 1], ['E5', 1], ['D5', 1], ['C5', 1.5], ['A4', 0.5], ['G4', 2],
            ['F4', 1], ['A4', 1], ['D5', 2], ['C5', 1], ['A4', 1], ['F4', 1.5], ['E4', 0.5], ['D4', 3], [null, 1],
            ['A4', 0.5], ['D5', 0.5], ['E5', 1], ['F5', 2], ['E5', 1], ['D5', 1], ['B4', 2],
            ['A4', 1], ['G4', 1], ['F4', 1.5], ['E4', 0.5], ['D4', 4], [null, 1]
        ]
    },
    {
        id: 'yuyake-koyake',
        englishTitle: 'Yuyake Koyake (Evening Sunset)',
        japaneseTitle: '夕焼け小焼け',
        category: 'Traditional & Folk',
        tempo: 82,
        droneChord: ['C3', 'G3', 'C4', 'E4'],
        melody: [
            ['G4', 1], ['G4', 1], ['A4', 1], ['G4', 1], ['E4', 1], ['G4', 1], ['C4', 2],
            ['D4', 1], ['E4', 1], ['G4', 1], ['A4', 1], ['G4', 3], [null, 1],
            ['A4', 1], ['C5', 1], ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1], ['E4', 2],
            ['G4', 1], ['A4', 1], ['E4', 1], ['D4', 1], ['C4', 3], [null, 1],
            ['C5', 1], ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1], ['E4', 1], ['C4', 2],
            ['D4', 1], ['E4', 1], ['G4', 1], ['A4', 1], ['G4', 3], [null, 1],
            ['A4', 1], ['C5', 1], ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1], ['E4', 2],
            ['G4', 1], ['A4', 1], ['D4', 1], ['E4', 1], ['C4', 3], [null, 1]
        ]
    },
    {
        id: 'akatombo',
        englishTitle: 'Akatombo (Red Dragonfly)',
        japaneseTitle: '赤とんぼ',
        category: 'Traditional & Folk',
        tempo: 72,
        droneChord: ['F2', 'C3', 'F3', 'A3'],
        melody: [
            ['C4', 1.5], ['F4', 0.5], ['G4', 1], ['A4', 2], ['C5', 1],
            ['D5', 1.5], ['C5', 0.5], ['A4', 1], ['G4', 3],
            ['A4', 1.5], ['C5', 0.5], ['D5', 1], ['C5', 2], ['A4', 1],
            ['G4', 1.5], ['F4', 0.5], ['D4', 1], ['C4', 3],
            ['D4', 1.5], ['F4', 0.5], ['G4', 1], ['A4', 2], ['C5', 1],
            ['D5', 1.5], ['C5', 0.5], ['A4', 1], ['G4', 3],
            ['A4', 1.5], ['G4', 0.5], ['F4', 1], ['D4', 2], ['G4', 1],
            ['F4', 3], [null, 1]
        ]
    },
    {
        id: 'furusato',
        englishTitle: 'Furusato (Hometown)',
        japaneseTitle: '故郷',
        category: 'Traditional & Folk',
        tempo: 84,
        droneChord: ['G2', 'D3', 'G3', 'D4'],
        melody: [
            ['D4', 1], ['G4', 1], ['G4', 1],
            ['G4', 1.5], ['A4', 0.5], ['B4', 1],
            ['A4', 1], ['G4', 1], ['A4', 1],
            ['D5', 2], [null, 1],
            ['B4', 1], ['B4', 1], ['C5', 1],
            ['D5', 2], ['E5', 1],
            ['D5', 1], ['B4', 1], ['G4', 1],
            ['A4', 2], [null, 1],
            ['D5', 1], ['B4', 1], ['G4', 1],
            ['A4', 1.5], ['B4', 0.5], ['C5', 1],
            ['B4', 1], ['G4', 1], ['E4', 1],
            ['D4', 2], [null, 1],
            ['D4', 1], ['G4', 1], ['A4', 1],
            ['B4', 2], ['C5', 1],
            ['D5', 1], ['B4', 1], ['A4', 1],
            ['G4', 2.5], [null, 0.5]
        ]
    },
    {
        id: 'kojo-no-tsuki',
        englishTitle: 'Kojo no Tsuki (Moon over Ruined Castle)',
        japaneseTitle: '荒城の月',
        category: 'Traditional & Folk',
        tempo: 68,
        droneChord: ['D3', 'A3', 'D4', 'F4'],
        melody: [
            ['D4', 1], ['D4', 1], ['A4', 1.5], ['G4', 0.5], ['F4', 1], ['E4', 1], ['D4', 2],
            ['F4', 1], ['G4', 1], ['A4', 1], ['C5', 1], ['D5', 2], ['A4', 2],
            ['D5', 1], ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1.5], ['A4', 0.5], ['F4', 2],
            ['E4', 1], ['D4', 1], ['F4', 1], ['E4', 1], ['D4', 3], [null, 1]
        ]
    },
    {
        id: 'takeda-lullaby',
        englishTitle: 'Takeda Lullaby (Takeda no Komoriuta)',
        japaneseTitle: '竹田の子守唄',
        category: 'Traditional & Folk',
        tempo: 72,
        droneChord: ['D3', 'A3', 'D4', 'F4'],
        melody: [
            ['D4', 1], ['F4', 1], ['A4', 2], ['G4', 1], ['F4', 1], ['D4', 2],
            ['F4', 1], ['G4', 1], ['A4', 2], ['D5', 1], ['C5', 1], ['A4', 2],
            ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['D4', 1], ['C4', 2],
            ['D4', 1], ['F4', 1], ['G4', 1.5], ['F4', 0.5], ['D4', 3], [null, 1],
            ['D4', 1], ['F4', 1], ['A4', 2], ['G4', 1], ['F4', 1], ['D4', 2],
            ['F4', 1], ['G4', 1], ['A4', 2], ['D5', 1], ['C5', 1], ['A4', 2],
            ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['D4', 1], ['C4', 2],
            ['D4', 1], ['F4', 1], ['G4', 1.5], ['F4', 0.5], ['D4', 3], [null, 1]
        ]
    },
    {
        id: 'edo-lullaby',
        englishTitle: 'Edo Lullaby (Edo no Komoriuta)',
        japaneseTitle: '江戸子守唄',
        category: 'Traditional & Folk',
        tempo: 70,
        droneChord: ['D3', 'A3', 'D4', 'F4'],
        melody: [
            ['D4', 2], ['F4', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['D4', 2],
            ['F4', 1], ['G4', 1], ['A4', 2], ['A4', 2], [null, 1],
            ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['D4', 1], ['C4', 2],
            ['D4', 1], ['F4', 1], ['G4', 1.5], ['F4', 0.5], ['D4', 3], [null, 1],
            ['A4', 1], ['C5', 1], ['D5', 2], ['C5', 1], ['A4', 1], ['G4', 2],
            ['F4', 1], ['G4', 1], ['A4', 2], ['F4', 1], ['E4', 1], ['D4', 2],
            ['D4', 1], ['F4', 1], ['G4', 1], ['A4', 1], ['F4', 1], ['D4', 1], ['C4', 2],
            ['D4', 1], ['F4', 1], ['G4', 1.5], ['F4', 0.5], ['D4', 3], [null, 1]
        ]
    },
    {
        id: 'zuizuizukkorobashi',
        englishTitle: "Zuizuizukkorobashi (Children's Game Song)",
        japaneseTitle: 'ずいずいずっころばし',
        category: 'Traditional & Folk',
        tempo: 110,
        droneChord: ['D3', 'A3', 'D4', 'F4'],
        melody: [
            ['D4', 1], ['D4', 1], ['F4', 1], ['F4', 1], ['D4', 1], ['F4', 1], ['A4', 2],
            ['G4', 1], ['G4', 1], ['F4', 1], ['D4', 1], ['F4', 1], ['G4', 1], ['A4', 2],
            ['D5', 1], ['D5', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['D4', 1], ['C4', 2],
            ['D4', 1], ['F4', 1], ['G4', 1], ['F4', 1], ['D4', 2], [null, 1],
            ['A4', 1], ['A4', 1], ['F4', 1], ['D4', 1], ['A4', 1], ['A4', 1], ['F4', 1], ['D4', 1],
            ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['D4', 1], ['C4', 2],
            ['D4', 1], ['F4', 1], ['G4', 1], ['A4', 1], ['F4', 1], ['D4', 1], ['D4', 2],
            ['A4', 1], ['A4', 1], ['F4', 1], ['D4', 1], ['D4', 3], [null, 1]
        ]
    },

    // --- Modern & Cinematic Melodies ---
    {
        id: 'sukiyaki',
        englishTitle: 'Sukiyaki (Ue o Muite Arukou)',
        japaneseTitle: '上を向いて歩こう',
        category: 'Modern & Cinematic',
        tempo: 114,
        droneChord: ['G2', 'D3', 'G3', 'B3'],
        melody: [
            // Verse 1
            ['B4', 1.5], ['B4', 0.5], ['B4', 1], ['B4', 1], ['C5', 1], ['D5', 2],
            ['B4', 1], ['B4', 1], ['B4', 1], ['A4', 1], ['G4', 2],
            ['E4', 1], ['G4', 1], ['A4', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['E4', 3], [null, 1],
            ['B4', 1.5], ['B4', 0.5], ['B4', 1], ['B4', 1], ['C5', 1], ['D5', 2],
            ['B4', 1], ['B4', 1], ['B4', 1], ['A4', 1], ['G4', 3], [null, 1],
            // Chorus (Bridge)
            ['D5', 1.5], ['D5', 0.5], ['D5', 1], ['D5', 1], ['E5', 1], ['D5', 1], ['B4', 2],
            ['D5', 1.5], ['D5', 0.5], ['D5', 1], ['D5', 1], ['E5', 1], ['D5', 1], ['B4', 3], [null, 1],
            // Chorus Return
            ['B4', 1.5], ['B4', 0.5], ['B4', 1], ['B4', 1], ['C5', 1], ['D5', 2],
            ['B4', 1], ['B4', 1], ['B4', 1], ['A4', 1], ['G4', 2],
            ['A4', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['E4', 2], ['D4', 2],
            ['G4', 1], ['G4', 1], ['A4', 1], ['A4', 1], ['G4', 3], [null, 1]
        ]
    },
    {
        id: 'mr-lawrence',
        englishTitle: 'Merry Christmas, Mr. Lawrence',
        japaneseTitle: '戦場のメリークリスマス',
        category: 'Modern & Cinematic',
        tempo: 96,
        droneChord: ['Eb3', 'Bb3', 'Eb4', 'G4'],
        melody: [
            // Theme Part A
            ['D5', 1], ['E5', 1], ['G5', 1], ['A5', 1], ['E5', 2], ['D5', 2],
            ['D5', 1], ['E5', 1], ['G5', 1], ['A5', 1], ['E5', 4],
            ['D5', 1], ['E5', 1], ['G5', 1], ['A5', 1], ['B5', 1], ['A5', 1], ['G5', 1], ['E5', 1],
            ['D5', 1], ['E5', 1], ['G5', 1], ['A5', 1], ['G5', 4],
            // Theme Part B (Climax Refrain)
            ['G5', 1], ['A5', 1], ['B5', 1], ['D6', 1], ['B5', 2], ['A5', 2],
            ['G5', 1], ['A5', 1], ['B5', 1], ['D6', 1], ['E6', 4],
            ['E6', 1], ['D6', 1], ['B5', 1], ['A5', 1], ['G5', 1], ['E5', 1], ['D5', 2],
            ['D5', 1], ['E5', 1], ['G5', 1], ['A5', 1], ['G5', 4], [null, 1]
        ]
    },
    {
        id: 'summer',
        englishTitle: 'Summer (Joe Hisaishi)',
        japaneseTitle: 'Summer (菊次郎の夏)',
        category: 'Modern & Cinematic',
        tempo: 124,
        droneChord: ['D3', 'A3', 'D4', 'F#4'],
        melody: [
            // Opening Theme Motif
            ['A4', 0.5], ['D5', 0.5], ['F#5', 0.5], ['A5', 0.5], ['F#5', 1], ['D5', 1],
            ['A4', 0.5], ['D5', 0.5], ['F#5', 0.5], ['A5', 0.5], ['F#5', 1], ['D5', 1],
            ['G4', 0.5], ['B4', 0.5], ['D5', 0.5], ['G5', 0.5], ['E5', 2],
            ['A4', 0.5], ['D5', 0.5], ['F#5', 0.5], ['A5', 0.5], ['F#5', 1], ['D5', 1],
            // Main Chorus Theme
            ['F#5', 1], ['E5', 1], ['D5', 1], ['A4', 1], ['B4', 1], ['D5', 1], ['E5', 2],
            ['F#5', 1], ['E5', 1], ['D5', 1], ['A4', 1], ['B4', 1], ['D5', 1], ['D5', 2],
            ['F#5', 1], ['E5', 1], ['D5', 1], ['A4', 1], ['B4', 1], ['D5', 1], ['E5', 1.5], ['F#5', 0.5],
            ['E5', 1], ['D5', 1], ['B4', 1], ['A4', 1], ['D5', 3], [null, 1]
        ]
    },
    {
        id: 'always-with-me',
        englishTitle: 'Always With Me (Spirited Away)',
        japaneseTitle: 'いつも何度でも',
        category: 'Modern & Cinematic',
        tempo: 88,
        droneChord: ['F2', 'C3', 'F3', 'A3'],
        melody: [
            // Verse 1
            ['C4', 1], ['F4', 1], ['G4', 1], ['A4', 2], ['G4', 1],
            ['F4', 1], ['E4', 1], ['F4', 1], ['G4', 3],
            ['C4', 1], ['E4', 1], ['F4', 1], ['G4', 2], ['F4', 1],
            ['E4', 1], ['D4', 1], ['E4', 1], ['F4', 3],
            ['F4', 1], ['G4', 1], ['A4', 1], ['Bb4', 2], ['A4', 1],
            ['G4', 1], ['F4', 1], ['G4', 1], ['A4', 3],
            ['F4', 1], ['G4', 1], ['A4', 1], ['G4', 2], ['F4', 1],
            ['E4', 1], ['D4', 1], ['E4', 1], ['F4', 3], [null, 1],
            // Chorus
            ['C5', 1.5], ['A4', 0.5], ['Bb4', 1], ['C5', 2], ['D5', 1],
            ['C5', 1], ['Bb4', 1], ['A4', 1], ['G4', 3],
            ['Bb4', 1.5], ['G4', 0.5], ['A4', 1], ['Bb4', 2], ['C5', 1],
            ['A4', 1], ['G4', 1], ['F4', 1], ['E4', 2], ['G4', 1],
            ['F4', 3], [null, 1]
        ]
    },
    {
        id: 'path-of-the-wind',
        englishTitle: 'Path of the Wind (My Neighbor Totoro)',
        japaneseTitle: '風の通り道',
        category: 'Modern & Cinematic',
        tempo: 84,
        droneChord: ['E3', 'B3', 'E4', 'G4'],
        melody: [
            // Verse 1
            ['E4', 1], ['G4', 1], ['A4', 1], ['B4', 2], ['A4', 1],
            ['G4', 1], ['E4', 1], ['D4', 1], ['E4', 3],
            ['E4', 1], ['G4', 1], ['A4', 1], ['B4', 2], ['D5', 1],
            ['B4', 1], ['A4', 1], ['G4', 1], ['A4', 3], [null, 1],
            ['E4', 1], ['G4', 1], ['A4', 1], ['B4', 2], ['A4', 1],
            ['G4', 1], ['E4', 1], ['D4', 1], ['E4', 3],
            ['D4', 1], ['E4', 1], ['G4', 1], ['A4', 2], ['B4', 1],
            ['G4', 1], ['E4', 1], ['D4', 1], ['E4', 3], [null, 1],
            // Chorus
            ['B4', 1], ['D5', 1], ['E5', 1.5], ['D5', 0.5], ['B4', 1],
            ['A4', 1], ['B4', 1], ['D5', 1], ['B4', 3],
            ['G4', 1], ['A4', 1], ['B4', 1.5], ['A4', 0.5], ['G4', 1],
            ['E4', 1], ['G4', 1], ['A4', 1], ['B4', 3], [null, 1],
            ['B4', 1], ['D5', 1], ['E5', 1.5], ['D5', 0.5], ['B4', 1],
            ['A4', 1], ['B4', 1], ['D5', 1], ['E5', 3],
            ['D5', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['E4', 1], ['D4', 1],
            ['E4', 3], [null, 1]
        ]
    },
    {
        id: 'stay-with-me',
        englishTitle: 'Mayonaka no Door (Stay With Me)',
        japaneseTitle: '真夜中のドア〜Stay With Me',
        category: 'Modern & Cinematic',
        tempo: 108,
        droneChord: ['F3', 'C4', 'Eb4', 'Ab4'],
        melody: [
            // Chorus ("Stay with me...")
            ['Ab4', 2], ['Bb4', 1], ['C5', 2], ['Bb4', 1], ['Ab4', 2],
            ['F4', 1], ['Ab4', 1], ['Bb4', 1], ['C5', 1], ['Eb5', 1], ['C5', 2], ['Bb4', 1],
            ['Ab4', 1], ['Bb4', 1], ['C5', 2], ['Bb4', 1], ['Ab4', 1], ['F4', 3], [null, 1],
            ['Ab4', 2], ['Bb4', 1], ['C5', 2], ['Bb4', 1], ['Ab4', 2],
            ['C5', 1], ['Eb5', 1], ['F5', 2], ['Eb5', 1], ['C5', 1], ['Bb4', 1], ['Ab4', 3], [null, 1],
            // Verse 1
            ['F4', 1], ['Ab4', 1], ['Bb4', 1], ['C5', 1], ['C5', 1], ['Bb4', 1], ['Ab4', 1], ['F4', 2],
            ['Ab4', 1], ['Bb4', 1], ['C5', 1], ['Eb5', 1], ['C5', 2], ['Bb4', 2],
            ['F4', 1], ['Ab4', 1], ['Bb4', 1], ['C5', 1], ['C5', 1], ['Bb4', 1], ['Ab4', 1], ['F4', 2],
            ['Ab4', 1], ['Bb4', 1], ['C5', 1], ['Eb5', 1], ['F5', 3], [null, 1]
        ]
    },
    {
        id: 'plastic-love',
        englishTitle: 'Plastic Love',
        japaneseTitle: 'プラスティック・ラヴ',
        category: 'Modern & Cinematic',
        tempo: 112,
        droneChord: ['D3', 'A3', 'C4', 'F4'],
        melody: [
            // Verse 1
            ['D4', 1], ['F4', 1], ['G4', 1], ['A4', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['G4', 1], ['A4', 2],
            ['F4', 1], ['G4', 1], ['A4', 1], ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['G4', 2],
            ['D4', 1], ['F4', 1], ['G4', 1], ['A4', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['G4', 1], ['A4', 2],
            ['F4', 1], ['G4', 1], ['A4', 1], ['C5', 1], ['D5', 2], ['C5', 1], ['A4', 1], ['G4', 3], [null, 1],
            // Chorus
            ['D5', 1], ['C5', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['A4', 3],
            ['D5', 1], ['C5', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['C5', 3],
            ['D5', 1], ['C5', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['A4', 1], ['B4', 1], ['C5', 1],
            ['D5', 3], [null, 1],
            ['D5', 1], ['C5', 1], ['A4', 1], ['G4', 1], ['F4', 1], ['D4', 3], [null, 1]
        ]
    },
    {
        id: 'first-love',
        englishTitle: 'First Love',
        japaneseTitle: 'First Love',
        category: 'Modern & Cinematic',
        tempo: 80,
        droneChord: ['G2', 'D3', 'G3', 'B3'],
        melody: [
            // Verse 1
            ['G4', 1], ['G4', 0.5], ['A4', 0.5], ['B4', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['E4', 2],
            ['E4', 1], ['G4', 1], ['A4', 1], ['B4', 1], ['G4', 2], ['D4', 2],
            ['E4', 1], ['G4', 1], ['A4', 1], ['B4', 1], ['A4', 2], ['G4', 2],
            ['G4', 1], ['G4', 0.5], ['A4', 0.5], ['B4', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['E4', 2],
            ['E4', 1], ['G4', 1], ['A4', 1], ['C5', 1], ['B4', 1], ['A4', 1], ['G4', 2],
            ['A4', 1], ['B4', 1], ['C5', 1], ['D5', 1], ['B4', 3], [null, 1],
            // Chorus
            ['E4', 1], ['G4', 1], ['A4', 1], ['C5', 1], ['B4', 1], ['A4', 1], ['G4', 2],
            ['A4', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['E4', 2], ['D4', 2],
            ['E4', 1], ['G4', 1], ['A4', 1], ['C5', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['E5', 1],
            ['D5', 3], [null, 1],
            ['E4', 1], ['G4', 1], ['A4', 1], ['C5', 1], ['B4', 1], ['A4', 1], ['G4', 2],
            ['A4', 1], ['B4', 1], ['A4', 1], ['G4', 1], ['E4', 2], ['D4', 2],
            ['E4', 1], ['G4', 1], ['A4', 1], ['C5', 1], ['B4', 1], ['A4', 1], ['G4', 2], ['G4', 2]
        ]
    }
];

class NagoyaSoundscape {
    constructor() {
        this.ctx = null;
        this.masterGain = null;
        this.droneOscs = [];
        this.droneGains = [];
        this.timer = null;
        this.isPlaying = false;
        this.hasStartedBefore = false;
        this.songs = [...FAMOUS_JAPANESE_SONGS];
        this.currentSongIndex = 0;
        this.currentNoteIndex = 0;

        // Randomize the song order immediately on load
        this.shufflePlaylist();
    }

    shufflePlaylist(excludeSongId = null) {
        const list = [...FAMOUS_JAPANESE_SONGS];
        for (let i = list.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [list[i], list[j]] = [list[j], list[i]];
        }
        // Ensure the first song in new cycle doesn't repeat the last song
        if (excludeSongId && list.length > 1 && list[0].id === excludeSongId) {
            const swapIdx = 1 + Math.floor(Math.random() * (list.length - 1));
            [list[0], list[swapIdx]] = [list[swapIdx], list[0]];
        }
        this.songs = list;
        this.currentSongIndex = 0;
        this.currentNoteIndex = 0;
    }

    pickDifferentSong() {
        const currentSong = this.getCurrentSong();
        const currentId = currentSong ? currentSong.id : null;
        const candidates = this.songs.filter(s => s.id !== currentId);
        if (candidates.length > 0) {
            const chosen = candidates[Math.floor(Math.random() * candidates.length)];
            const idx = this.songs.findIndex(s => s.id === chosen.id);
            this.currentSongIndex = idx !== -1 ? idx : 0;
        } else {
            this.currentSongIndex = (this.currentSongIndex + 1) % this.songs.length;
        }
        this.currentNoteIndex = 0;
    }

    init() {
        if (!this.ctx) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            this.ctx = new AudioCtx();

            this.masterGain = this.ctx.createGain();
            this.masterGain.gain.setValueAtTime(0.0001, this.ctx.currentTime);
            this.masterGain.connect(this.ctx.destination);
        }
    }

    getCurrentSong() {
        return this.songs[this.currentSongIndex] || null;
    }

    notifySongChange() {
        const song = this.getCurrentSong();
        if (song) {
            window.dispatchEvent(new CustomEvent('nagoya:song-changed', {
                detail: {
                    id: song.id,
                    title: `${song.englishTitle} (${song.japaneseTitle})`,
                    englishTitle: song.englishTitle,
                    japaneseTitle: song.japaneseTitle,
                    category: song.category || 'Japanese Classic'
                }
            }));
        }
    }

    stopDrone() {
        if (!this.ctx) return;
        this.droneOscs.forEach((osc, idx) => {
            try {
                if (this.droneGains[idx]) {
                    this.droneGains[idx].gain.linearRampToValueAtTime(0.0001, this.ctx.currentTime + 0.8);
                }
                setTimeout(() => {
                    try { osc.stop(); } catch (e) { }
                }, 900);
            } catch (e) { }
        });
        this.droneOscs = [];
        this.droneGains = [];
    }

    setDroneChord(noteNames) {
        if (!this.ctx) return;
        this.stopDrone();

        const chordFreqs = noteNames.map(name => NOTE_FREQS[name] || 220);
        this.droneOscs = [];
        this.droneGains = [];

        chordFreqs.forEach((freq, idx) => {
            const osc = this.ctx.createOscillator();
            const filter = this.ctx.createBiquadFilter();
            const gain = this.ctx.createGain();

            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, this.ctx.currentTime);

            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(420 + idx * 110, this.ctx.currentTime);

            const targetVol = 0.03 / (idx * 0.35 + 1);
            gain.gain.setValueAtTime(0.0001, this.ctx.currentTime);
            gain.gain.linearRampToValueAtTime(targetVol, this.ctx.currentTime + 1.2);

            osc.connect(filter);
            filter.connect(gain);
            gain.connect(this.masterGain);

            osc.start();
            this.droneOscs.push(osc);
            this.droneGains.push(gain);
        });
    }

    pluckKotoNote(freq, velocity = 0.17) {
        if (!this.ctx || !this.isPlaying) return;

        const now = this.ctx.currentTime;
        const osc = this.ctx.createOscillator();
        const harmonicOsc = this.ctx.createOscillator();
        const pickOsc = this.ctx.createOscillator();
        const filter = this.ctx.createBiquadFilter();
        const noteGain = this.ctx.createGain();
        const pickGain = this.ctx.createGain();

        // Warm plucked string
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(freq, now);

        harmonicOsc.type = 'sine';
        harmonicOsc.frequency.setValueAtTime(freq * 2, now);

        // Crisp acoustic pick transient attack
        pickOsc.type = 'triangle';
        pickOsc.frequency.setValueAtTime(freq * 3, now);
        pickGain.gain.setValueAtTime(velocity * 0.35, now);
        pickGain.gain.exponentialRampToValueAtTime(0.0001, now + 0.04);
        pickOsc.connect(pickGain);
        pickGain.connect(this.masterGain);

        filter.type = 'lowpass';
        filter.frequency.setValueAtTime(Math.min(freq * 3.8, 4800), now);
        filter.frequency.exponentialRampToValueAtTime(Math.max(freq * 1.2, 320), now + 2.2);

        noteGain.gain.setValueAtTime(0.0001, now);
        noteGain.gain.linearRampToValueAtTime(velocity, now + 0.012);
        noteGain.gain.exponentialRampToValueAtTime(0.0001, now + 2.6);

        osc.connect(filter);
        harmonicOsc.connect(filter);
        filter.connect(noteGain);
        noteGain.connect(this.masterGain);

        osc.start(now);
        harmonicOsc.start(now);
        pickOsc.start(now);
        osc.stop(now + 2.8);
        harmonicOsc.stop(now + 2.8);
        pickOsc.stop(now + 0.06);
    }

    playStep() {
        if (!this.isPlaying) return;

        const song = this.songs[this.currentSongIndex];
        if (!song) return;

        const noteInfo = song.melody[this.currentNoteIndex];
        if (!noteInfo) {
            // End of current song: cycle smoothly to next song in randomized order
            const lastSongId = song.id;
            this.currentSongIndex++;
            if (this.currentSongIndex >= this.songs.length) {
                this.shufflePlaylist(lastSongId);
            } else {
                this.currentNoteIndex = 0;
            }

            const nextSong = this.getCurrentSong();
            this.setDroneChord(nextSong.droneChord);
            this.notifySongChange();

            // Interlude pause between songs (2.2s)
            this.timer = setTimeout(() => {
                this.playStep();
            }, 2200);
            return;
        }

        const [noteName, beats] = noteInfo;
        const beatMs = (60 / song.tempo) * 1000;
        const durationMs = beats * beatMs;

        if (noteName && NOTE_FREQS[noteName]) {
            const freq = NOTE_FREQS[noteName];
            this.pluckKotoNote(freq, 0.17);
        }

        this.currentNoteIndex++;
        this.timer = setTimeout(() => {
            this.playStep();
        }, durationMs);
    }

    start(forceNewSong = false) {
        this.init();
        if (this.ctx && this.ctx.state === 'suspended') {
            this.ctx.resume();
        }

        if (this.isPlaying) return;

        // If resuming after a pause or explicitly requested, switch to a different song
        if (this.hasStartedBefore || forceNewSong) {
            this.pickDifferentSong();
        }
        this.hasStartedBefore = true;

        this.isPlaying = true;
        this.masterGain.gain.cancelScheduledValues(this.ctx.currentTime);
        this.masterGain.gain.setValueAtTime(this.masterGain.gain.value, this.ctx.currentTime);
        this.masterGain.gain.linearRampToValueAtTime(0.32, this.ctx.currentTime + 1.2);

        const currentSong = this.getCurrentSong();
        this.setDroneChord(currentSong.droneChord);
        this.notifySongChange();
        this.playStep();
    }

    stop() {
        if (!this.ctx || !this.isPlaying) return;

        this.isPlaying = false;
        clearTimeout(this.timer);

        const now = this.ctx.currentTime;
        this.masterGain.gain.cancelScheduledValues(now);
        this.masterGain.gain.setValueAtTime(this.masterGain.gain.value, now);
        this.masterGain.gain.linearRampToValueAtTime(0.0001, now + 0.8);

        this.stopDrone();
    }
}

const nagoyaSoundscape = new NagoyaSoundscape();

function setupAlpineAudioStore() {
    if (window.Alpine && !window.Alpine.store('audio')) {
        const isMuted = localStorage.getItem('nagoya_music_enabled') === 'false';
        const initialSong = nagoyaSoundscape.getCurrentSong();

        window.Alpine.store('audio', {
            // Music plays by default unless explicitly muted by user
            isPlaying: !isMuted,
            currentSongTitle: initialSong ? `${initialSong.englishTitle} (${initialSong.japaneseTitle})` : 'Sakura Sakura (さくら さくら)',
            currentSongEnglish: initialSong ? initialSong.englishTitle : 'Sakura Sakura',
            currentSongJapanese: initialSong ? initialSong.japaneseTitle : 'さくら さくら',
            currentSongCategory: initialSong ? (initialSong.category || 'Traditional & Folk') : 'Traditional & Folk',

            init() {
                if (localStorage.getItem('nagoya_music_enabled') !== 'false') {
                    this.play(false);

                    // Modern browsers require a user interaction to resume audio context if autoplay policy triggers
                    const unlockAutoplay = () => {
                        if (localStorage.getItem('nagoya_music_enabled') !== 'false') {
                            if (!nagoyaSoundscape.isPlaying) {
                                this.play(false);
                            } else if (nagoyaSoundscape.ctx && nagoyaSoundscape.ctx.state === 'suspended') {
                                nagoyaSoundscape.ctx.resume();
                            }
                        }
                        ['click', 'keydown', 'pointerdown', 'touchstart'].forEach(evt => {
                            window.removeEventListener(evt, unlockAutoplay);
                        });
                    };

                    ['click', 'keydown', 'pointerdown', 'touchstart'].forEach(evt => {
                        window.addEventListener(evt, unlockAutoplay, { passive: true, once: true });
                    });
                }

                window.addEventListener('nagoya:song-changed', (e) => {
                    if (e.detail) {
                        this.currentSongTitle = e.detail.title;
                        this.currentSongEnglish = e.detail.englishTitle;
                        this.currentSongJapanese = e.detail.japaneseTitle;
                        this.currentSongCategory = e.detail.category || 'Japanese Classic';
                    }
                });
            },

            toggle() {
                if (this.isPlaying) {
                    this.pause();
                } else {
                    // Turn back on with a different song than was previously playing
                    this.play(true);
                }
            },

            play(forceDifferent = false) {
                this.isPlaying = true;
                localStorage.setItem('nagoya_music_enabled', 'true');
                nagoyaSoundscape.start(forceDifferent);
                const song = nagoyaSoundscape.getCurrentSong();
                if (song) {
                    this.currentSongTitle = `${song.englishTitle} (${song.japaneseTitle})`;
                    this.currentSongEnglish = song.englishTitle;
                    this.currentSongJapanese = song.japaneseTitle;
                    this.currentSongCategory = song.category || 'Japanese Classic';
                }
            },

            pause() {
                this.isPlaying = false;
                localStorage.setItem('nagoya_music_enabled', 'false');
                nagoyaSoundscape.stop();
            }
        });
    }
}

function setupAlpineThemeStore() {
    if (window.Alpine && !window.Alpine.store('theme')) {
        window.Alpine.store('theme', {
            mode: (typeof window.__getTheme === 'function' ? window.__getTheme() : (localStorage.getItem('theme') || 'dark')),

            init() {
                this.mode = (typeof window.__getTheme === 'function' ? window.__getTheme() : (localStorage.getItem('theme') || 'dark'));
                this.apply();
                window.addEventListener('theme-changed', (e) => {
                    if (e.detail && e.detail.mode) {
                        this.mode = e.detail.mode;
                    }
                });
            },

            toggle() {
                if (typeof window.__toggleTheme === 'function') {
                    window.__toggleTheme();
                    this.mode = typeof window.__getTheme === 'function' ? window.__getTheme() : (localStorage.getItem('theme') || 'dark');
                } else {
                    this.mode = this.mode === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('theme', this.mode);
                    this.apply();
                    window.dispatchEvent(new CustomEvent('theme-changed', { detail: { mode: this.mode } }));
                }
            },

            apply() {
                if (typeof window.__applyTheme === 'function') {
                    window.__applyTheme(this.mode);
                } else {
                    if (this.mode === 'dark') {
                        document.documentElement.classList.add('dark');
                    } else {
                        document.documentElement.classList.remove('dark');
                    }
                }
            }
        });
    }
}

function initAlpineExtensions() {
    if (window.Alpine) {
        window.Alpine.data('nagoyaMap', window.nagoyaMap);
        setupAlpineAudioStore();
        setupAlpineThemeStore();
    }
}

initAlpineExtensions();
document.addEventListener('alpine:init', initAlpineExtensions);
document.addEventListener('livewire:init', initAlpineExtensions);
document.addEventListener('DOMContentLoaded', initAlpineExtensions);
