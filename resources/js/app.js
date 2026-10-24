// Nagoya Oikos Map Controller for Alpine.js & Leaflet
window.nagoyaMap = function(config) {
    return {
        map: null,
        geoJsonLayer: null,
        churchesLayer: null,
        oikosLayer: null,
        spotsLayer: null,

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
        }
    };
};

if (window.Alpine) {
    window.Alpine.data('nagoyaMap', window.nagoyaMap);
}
document.addEventListener('alpine:init', () => {
    if (window.Alpine) {
        window.Alpine.data('nagoyaMap', window.nagoyaMap);
    }
});
