import { CONFIG } from '../config/constants.js';

export class MapService {
    constructor() {
        this.map = null;
        this.markers = [];
        this.routingControl = null;
        this.mapInitialized = false;
    }

    initMap(containerId) {
        if (this.mapInitialized) return;

        try {
            this.map = L.map(containerId, {
                center: CONFIG.MAP.DEFAULT_CENTER,
                zoom: CONFIG.MAP.DEFAULT_ZOOM,
                zoomControl: true,
                attributionControl: true,
                preferCanvas: true,
                maxZoom: CONFIG.MAP.MAX_ZOOM,
                minZoom: CONFIG.MAP.MIN_ZOOM
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 18,
                tileSize: 256,
                zoomOffset: 0,
                crossOrigin: true,
                updateWhenIdle: true,
                updateWhenZooming: false,
                keepBuffer: 2
            }).addTo(this.map);

            this.map.whenReady(() => {
                this.addDepotMarker();
                this.mapInitialized = true;
                setTimeout(() => {
                    this.map.invalidateSize();
                }, 200);
            });

        } catch (error) {
            console.error('Map initialization failed:', error);
        }
    }

    addDepotMarker() {
        const depotIcon = L.divIcon({
            className: 'depot-marker',
            html: '<i class="fas fa-warehouse"></i>',
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });

        L.marker(CONFIG.MAP.DEPOT_LOCATION, { icon: depotIcon })
            .addTo(this.map)
            .bindPopup('<strong>Main Depot</strong><br>Warsaw Distribution Center');
    }

    addOrderMarkers(orders) {
        this.clearMarkers();

        orders.forEach((order, index) => {
            const orderIcon = L.divIcon({
                className: 'custom-marker',
                html: `<div class="custom-marker" style="background-color: ${CONFIG.COLORS.PRIORITY[order.priority]}">${index + 1}</div>`,
                iconSize: [24, 24],
                iconAnchor: [12, 12]
            });

            const marker = L.marker([order.coordinates[0], order.coordinates[1]], { icon: orderIcon })
                .addTo(this.map)
                .bindPopup(this.createOrderPopup(order, index + 1), {
                    maxWidth: 250,
                    className: 'custom-popup'
                });

            this.markers.push(marker);
        });
    }

    createOrderPopup(order, index) {
        return `
            <div class="p-2 min-w-[200px]">
                <strong>Order #${order.id}</strong><br>
                <div class="text-sm">${order.client_name}</div>
                <div class="text-sm text-gray-600">${order.address}</div>
                <div class="text-lg font-semibold text-green-600 mt-1">zł${order.total_amount}</div>
                <div class="text-xs uppercase font-medium" style="color: ${CONFIG.COLORS.PRIORITY[order.priority]}">${order.priority} priority</div>
            </div>
        `;
    }

    clearMarkers() {
        this.markers.forEach(marker => {
            this.map.removeLayer(marker);
        });
        this.markers = [];
    }

    visualizeRoute(coordinates) {
        if (!this.map || !this.mapInitialized) {
            console.warn('Map not initialized yet');
            return;
        }

        if (this.routingControl) {
            this.map.removeControl(this.routingControl);
            this.routingControl = null;
        }

        try {
            this.routingControl = L.Routing.control({
                waypoints: coordinates.map(coord => L.latLng(coord[0], coord[1])),
                routeWhileDragging: false,
                addWaypoints: false,
                show: false,
                createMarker: function() { return null; },
                lineOptions: {
                    styles: [{ color: CONFIG.COLORS.ROUTE, opacity: 0.8, weight: 4 }]
                },
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://router.project-osrm.org/route/v1',
                    profile: 'driving'
                })
            });

            this.routingControl.addTo(this.map);

            setTimeout(() => {
                const group = new L.featureGroup(this.markers);
                this.map.fitBounds(group.getBounds().pad(0.1));
            }, 500);

        } catch (error) {
            console.error('Route visualization failed:', error);
        }
    }

    getMap() {
        return this.map;
    }
}