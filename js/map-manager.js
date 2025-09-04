class MapManager {
    constructor(data) {
        this.data = data;
        this.priorityColors = {
            'high': '#ef4444',
            'medium': '#f59e0b',
            'low': '#10b981'
        };
        this.depotCoordinates = [52.2297, 21.0122]; // Warsaw
    }

    init() {
        if (this.data.mapInitialized) return;

        try {
            console.log('Initializing map...');
            
            this.data.map = L.map('map', {
                center: [52.0, 19.0],
                zoom: 6,
                zoomControl: true,
                attributionControl: true,
                preferCanvas: true, 
                maxZoom: 18,
                minZoom: 5
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
            }).addTo(this.data.map);

            this.data.map.whenReady(() => {
                console.log('Map ready, adding markers...');
                this.addDepotMarker();
                this.addOrderMarkers();
                this.data.mapInitialized = true;

                setTimeout(() => {
                    this.data.map.invalidateSize();
                }, 200);
            });

        } catch (error) {
            console.error('Map initialization failed:', error);
        }
    }

    addDepotMarker() {
        try {
            const depotIcon = L.divIcon({
                className: 'depot-marker',
                html: '<i class="fas fa-warehouse"></i>',
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });

            const depotMarker = L.marker(this.depotCoordinates, { icon: depotIcon })
                .addTo(this.data.map)
                .bindPopup(`
                    <div class="p-3">
                        <strong class="text-lg">Main Depot</strong><br>
                        <div class="text-sm text-gray-600">Warsaw Distribution Center</div>
                        <div class="text-sm text-blue-600 mt-1">Starting Point for All Routes</div>
                    </div>
                `, {
                    maxWidth: 200,
                    className: 'custom-popup'
                });

            console.log('Depot marker added successfully');
            return depotMarker;
        } catch (error) {
            console.error('Failed to add depot marker:', error);
        }
    }

    addOrderMarkers() {
        try {
            this.clearOrderMarkers();

            console.log(`Adding ${this.data.orders.length} order markers...`);

            this.data.orders.forEach((order, index) => {
                const orderIcon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div class="custom-marker" style="background-color: ${this.priorityColors[order.priority]}">${index + 1}</div>`,
                    iconSize: [24, 24],
                    iconAnchor: [12, 12]
                });

                const marker = L.marker([order.coordinates[0], order.coordinates[1]], {
                        icon: orderIcon,
                        title: `Order #${order.id} - ${order.client_name}`
                    })
                    .addTo(this.data.map)
                    .bindPopup(this.createOrderPopup(order, index + 1), {
                        maxWidth: 250,
                        className: 'custom-popup'
                    });

                this.data.markers.push(marker);
            });

            console.log(`Successfully added ${this.data.markers.length} markers`);
        } catch (error) {
            console.error('Failed to add order markers:', error);
        }
    }

    createOrderPopup(order, orderNumber) {
        return `
            <div class="p-2 min-w-[200px]">
                <div class="flex items-center justify-between mb-2">
                    <strong class="text-lg">Order #${order.id}</strong>
                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-white text-sm font-bold" 
                         style="background-color: ${this.priorityColors[order.priority]}">${orderNumber}</div>
                </div>
                <div class="text-sm font-medium text-gray-800">${order.client_name}</div>
                <div class="text-sm text-gray-600 mb-2">${order.address}</div>
                <div class="flex items-center justify-between">
                    <div class="text-lg font-semibold text-green-600">zł${order.total_amount}</div>
                    <div class="text-xs uppercase font-medium px-2 py-1 rounded" 
                         style="background-color: ${this.priorityColors[order.priority]}20; color: ${this.priorityColors[order.priority]}">${order.priority} priority</div>
                </div>
                <div class="text-xs text-gray-500 mt-1">Status: ${order.status}</div>
            </div>
        `;
    }

    clearOrderMarkers() {
        this.data.markers.forEach(marker => {
            if (this.data.map.hasLayer(marker)) {
                this.data.map.removeLayer(marker);
            }
        });
        this.data.markers = [];
    }

    clearRoute() {
        if (this.data.routingControl) {
            this.data.map.removeControl(this.data.routingControl);
            this.data.routingControl = null;
        }
    }

    visualizeOptimizedRoute() {
        if (!this.data.map || !this.data.mapInitialized) {
            console.warn('Map not initialized yet');
            return;
        }

        if (!this.data.optimizationResult) {
            console.warn('No optimization result available');
            return;
        }

        console.log('Visualizing optimized route...');

        this.clearRoute();

        const routeCoordinates = this.buildRouteCoordinates();

        try {
            this.data.routingControl = L.Routing.control({
                waypoints: routeCoordinates.map(coord => L.latLng(coord[0], coord[1])),
                routeWhileDragging: false,
                addWaypoints: false,
                show: false,
                createMarker: function() {
                    return null; 
                },
                lineOptions: {
                    styles: [{
                        color: '#667eea',
                        opacity: 0.8,
                        weight: 4
                    }]
                },
                router: L.Routing.osrmv1({
                    serviceUrl: 'https://router.project-osrm.org/route/v1',
                    profile: 'driving'
                })
            });

            this.data.routingControl.addTo(this.data.map);

            setTimeout(() => {
                this.fitMapToRoute();
            }, 500);

            console.log('Route visualization completed');

        } catch (error) {
            console.error('Route visualization failed:', error);
        }
    }

    buildRouteCoordinates() {
        const coordinates = [this.depotCoordinates];
           
        if (this.data.optimizationResult && this.data.optimizationResult.route_steps) {
            this.data.optimizationResult.route_steps.forEach(step => {
                const order = this.data.orders.find(o => 
                    step.location.includes(o.address.split(',')[0]) || 
                    step.description.includes(o.client_name)
                );
                if (order) {
                    coordinates.push(order.coordinates);
                }
            });
        } else {
            // Fallback: use predefined optimized order
            const optimizedOrder = [
                this.depotCoordinates, // Warsaw (depot)
                [52.2297, 21.0122],   // Warsaw (order)
                [52.4064, 16.9252],   // Poznan
                [51.1079, 17.0385],   // Wroclaw
                [50.0647, 19.9450],   // Krakow
                [54.3520, 18.6466]    // Gdansk
            ];
            coordinates.push(...optimizedOrder.slice(1));
        }
        
        coordinates.push(this.depotCoordinates);
        
        return coordinates;
    }

    fitMapToRoute() {
        try {
            if (this.data.markers.length > 0) {
                const group = new L.featureGroup([...this.data.markers]);
                this.data.map.fitBounds(group.getBounds().pad(0.1));
            }
        } catch (error) {
            console.error('Failed to fit map to route:', error);
        }
    }

    refreshMarkers() {
        if (this.data.mapInitialized) {
            this.addOrderMarkers();
        }
    }

    focusOnOrder(orderId) {
        const order = this.data.orders.find(o => o.id === orderId);
        if (order && this.data.map) {
            this.data.map.setView(order.coordinates, 15);
            
            const markerIndex = this.data.orders.findIndex(o => o.id === orderId);
            if (markerIndex >= 0 && this.data.markers[markerIndex]) {
                this.data.markers[markerIndex].openPopup();
            }
        }
    }

    getMapBounds() {
        return this.data.map ? this.data.map.getBounds() : null;
    }
}