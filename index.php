<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poland Route Optimization - Professional Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <!-- Preload Leaflet CSS -->
    <link rel="preload" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" as="style" onload="this.rel='stylesheet'">
    <!-- Preload Leaflet Routing Machine CSS -->
    <link rel="preload" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" as="style" onload="this.rel='stylesheet'">

    <!-- Preload Font Awesome CSS -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style" onload="this.rel='stylesheet'">

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Routing Machine JS -->
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>
    <style>
        [x-cloak] {
            display: none !important;
        }

        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.9);
        }

        .custom-shadow {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .route-card {
            transition: all 0.3s ease;
        }

        .route-card:hover {
            transform: translateY(-2px);
        }

        .leaflet-routing-container {
            display: none;
        }

        #map {
            min-height: 500px;
            height: 32rem;
            width: 100%;
        }

        .custom-marker {
            width: 24px !important;
            height: 24px !important;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            border: 2px solid white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .depot-marker {
            width: 32px !important;
            height: 32px !important;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            border: 3px solid white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#667eea',
                        secondary: '#764ba2',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 min-h-screen">
    <div x-data="routeOptimizer()" x-cloak>
        <!-- Header -->
        <header class="gradient-bg text-white">
            <div class="container mx-auto px-6 py-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold flex items-center">
                            <i class="fas fa-route mr-3"></i>
                            Poland Route Optimizer
                        </h1>
                        <p class="text-blue-100 mt-2">Professional delivery route optimization for Polish cities</p>
                    </div>
                    <div class="text-right">
                        <div class="text-sm text-blue-100">Demo Server</div>
                        <div class="text-xs text-blue-200">147.135.252.51:3000</div>
                    </div>
                </div>
            </div>
        </header>

        <div class="container mx-auto px-6 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Control Panel -->
                <div class="lg:col-span-1 space-y-6 self-start sticky top-8">
                    <!-- Driver Selection -->
                    <div class="bg-white rounded-xl custom-shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-users text-primary mr-2"></i>
                            Select Driver
                        </h2>
                        <div class="space-y-3">
                            <template x-for="driver in drivers" :key="driver.id">
                                <div @click="selectedDriver = driver"
                                    :class="selectedDriver.id === driver.id ? 'ring-2 ring-primary bg-blue-50' : 'hover:bg-gray-50'"
                                    class="p-4 rounded-lg border cursor-pointer transition-all">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="font-medium text-gray-800" x-text="driver.full_name"></div>
                                            <div class="text-sm text-gray-500" x-text="driver.vehicle_details"></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-xs text-gray-400">License</div>
                                            <div class="text-sm font-mono" x-text="driver.license_number"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Delivery Orders -->
                    <div class="bg-white rounded-xl custom-shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-box text-primary mr-2"></i>
                            Today's Deliveries
                            <span class="ml-auto bg-primary text-white px-2 py-1 rounded-full text-sm" x-text="orders.length"></span>
                        </h2>
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            <template x-for="order in orders" :key="order.id">
                                <div class="p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="font-medium text-gray-800" x-text="'Order #' + order.id"></div>
                                            <div class="text-sm text-gray-600" x-text="order.client_name"></div>
                                            <div class="text-sm text-primary font-medium" x-text="order.address"></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-lg font-semibold text-green-600" x-text="'zł' + order.total_amount"></div>
                                            <div :class="order.status === 'pending' ? 'text-orange-500' : 'text-green-500'"
                                                class="text-xs font-medium uppercase" x-text="order.status"></div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Optimization Controls -->
                    <div class="bg-white rounded-xl custom-shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-cogs text-primary mr-2"></i>
                            Route Optimization
                        </h2>
                        <div class="space-y-4">
                            <button @click="optimizeRoutes()"
                                :disabled="loading"
                                class="w-full bg-gradient-to-r from-primary to-secondary text-white py-3 px-4 rounded-lg font-semibold hover:shadow-lg transition-all transform hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none">
                                <span x-show="!loading" class="flex items-center justify-center">
                                    <i class="fas fa-route mr-2"></i>
                                    Optimize Routes
                                </span>
                                <span x-show="loading" class="flex items-center justify-center">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Optimizing...
                                </span>
                            </button>

                            <!-- Quick Stats -->
                            <div x-show="optimizationResult" class="grid grid-cols-2 gap-3">
                                <div class="bg-green-50 p-3 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-green-600" x-text="Math.round((optimizationResult?.total_distance || 0) / 1000) + ' km'"></div>
                                    <div class="text-xs text-green-700 uppercase">Total Distance</div>
                                </div>
                                <div class="bg-blue-50 p-3 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-blue-600" x-text="Math.round((optimizationResult?.total_time || 0) / 60) + ' min'"></div>
                                    <div class="text-xs text-blue-700 uppercase">Total Time</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map and Results -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Map -->
                    <div class="bg-white rounded-xl custom-shadow overflow-hidden">
                        <div class="p-4 border-b bg-gray-50">
                            <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-map text-primary mr-2"></i>
                                Live Route Map
                                <span x-show="selectedDriver" class="ml-auto text-sm text-gray-600">
                                    Driver: <span class="font-medium" x-text="selectedDriver.full_name"></span>
                                </span>
                            </h2>
                        </div>
                        <div id="map" class="h-[32rem] w-full"></div>
                    </div>

                    <!-- Route Details -->
                    <div x-show="optimizationResult" class="bg-white rounded-xl custom-shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                            <i class="fas fa-list-ol text-primary mr-2"></i>
                            Optimized Route Details
                        </h2>
                        <div class="space-y-4">
                            <!-- Route Summary -->
                            <div class="bg-gradient-to-r from-green-50 to-blue-50 p-4 rounded-lg border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-trophy text-yellow-500 text-xl mr-3"></i>
                                        <div>
                                            <div class="font-semibold text-gray-800">Optimal Route Generated</div>
                                            <div class="text-sm text-gray-600">Best path for all deliveries</div>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-lg font-bold text-green-600" x-text="'Saved: ' + (optimizationResult?.savings || 0) + ' km'"></div>
                                        <div class="text-sm text-gray-500">vs. unoptimized</div>
                                    </div>
                                </div>
                            </div>
                            <!-- Route Steps -->
                            <div class="space-y-3">
                                <template x-for="(step, index) in (optimizationResult?.route_steps || [])" :key="index">
                                    <div class="route-card flex items-center p-4 border rounded-lg hover:shadow-md">
                                        <div class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-sm"
                                            x-text="index + 1"></div>
                                        <div class="ml-4 flex-1">
                                            <div class="font-medium text-gray-800" x-text="step.location"></div>
                                            <div class="text-sm text-gray-600" x-text="step.description"></div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-gray-700" x-text="step.distance"></div>
                                            <div class="text-xs text-gray-500" x-text="step.duration"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Loading Overlay -->
        <div x-show="loading" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="glass-effect rounded-xl p-8 text-center max-w-sm mx-4">
                <div class="animate-spin rounded-full h-16 w-16 border-4 border-primary border-t-transparent mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Optimizing Routes</h3>
                <p class="text-gray-600">Calculating the best delivery path...</p>
            </div>
        </div>
    </div>

    <script>
        function routeOptimizer() {
            return {
                // State
                selectedDriver: {},
                loading: false,
                optimizationResult: null,
                map: null,
                markers: [],
                routingControl: null,
                mapInitialized: false,

                // Static Demo Data
                drivers: [{
                        id: 1,
                        user_id: 101,
                        full_name: "Marek Kowalski",
                        license_number: "WAW123456",
                        vehicle_details: "Ford Transit - WX 1234A",
                        phone_number: "+48 123 456 789"
                    },
                    {
                        id: 2,
                        user_id: 102,
                        full_name: "Anna Nowak",
                        license_number: "KRK789012",
                        vehicle_details: "Mercedes Sprinter - KR 5678B",
                        phone_number: "+48 987 654 321"
                    },
                    {
                        id: 3,
                        user_id: 103,
                        full_name: "Piotr Wiśniewski",
                        license_number: "GDA345678",
                        vehicle_details: "Iveco Daily - GD 9012C",
                        phone_number: "+48 555 444 333"
                    }
                ],

                orders: [{
                        id: 1001,
                        client_name: "Jan Kowalczyk",
                        address: "Kraków, ul. Floriańska 25",
                        coordinates: [50.0647, 19.9450],
                        total_amount: 340,
                        status: "pending",
                        priority: "high"
                    },
                    {
                        id: 1002,
                        client_name: "Maria Szymańska",
                        address: "Warszawa, ul. Nowy Świat 15",
                        coordinates: [52.2297, 21.0122],
                        total_amount: 580,
                        status: "pending",
                        priority: "medium"
                    },
                    {
                        id: 1003,
                        client_name: "Andrzej Duda",
                        address: "Gdańsk, ul. Długa 30",
                        coordinates: [54.3520, 18.6466],
                        total_amount: 750,
                        status: "pending",
                        priority: "high"
                    },
                    {
                        id: 1004,
                        client_name: "Katarzyna Lewandowska",
                        address: "Wrocław, Rynek 40",
                        coordinates: [51.1079, 17.0385],
                        total_amount: 420,
                        status: "pending",
                        priority: "low"
                    },
                    {
                        id: 1005,
                        client_name: "Tomasz Zieliński",
                        address: "Poznań, Stary Rynek 10",
                        coordinates: [52.4064, 16.9252],
                        total_amount: 680,
                        status: "pending",
                        priority: "medium"
                    }
                ],

                init() {
                    this.selectedDriver = this.drivers[0];
                    // Delay map initialization to ensure DOM is ready
                    this.$nextTick(() => {
                        setTimeout(() => {
                            this.initMap();
                        }, 100);
                    });
                },

                initMap() {
                    if (this.mapInitialized) return;

                    try {
                        // Initialize map centered on Poland with better performance settings
                        this.map = L.map('map', {
                            center: [52.0, 19.0],
                            zoom: 6,
                            zoomControl: true,
                            attributionControl: true,
                            preferCanvas: true, // Better performance for many markers
                            maxZoom: 18,
                            minZoom: 5
                        });

                        // Add high-performance map tiles with caching
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors',
                            maxZoom: 18,
                            tileSize: 256,
                            zoomOffset: 0,
                            crossOrigin: true,
                            // Performance optimizations
                            updateWhenIdle: true,
                            updateWhenZooming: false,
                            keepBuffer: 2
                        }).addTo(this.map);

                        // Wait for map to fully load before adding markers
                        this.map.whenReady(() => {
                            this.addDepotMarker();
                            this.addOrderMarkers();
                            this.mapInitialized = true;

                            // Force map to recalculate size
                            setTimeout(() => {
                                this.map.invalidateSize();
                            }, 200);
                        });

                    } catch (error) {
                        console.error('Map initialization failed:', error);
                    }
                },

                addDepotMarker() {
                    // Simplified depot marker for better performance
                    const depotIcon = L.divIcon({
                        className: 'depot-marker',
                        html: '<i class="fas fa-warehouse"></i>',
                        iconSize: [32, 32],
                        iconAnchor: [16, 16]
                    });

                    L.marker([52.2297, 21.0122], {
                            icon: depotIcon
                        })
                        .addTo(this.map)
                        .bindPopup('<strong>Main Depot</strong><br>Warsaw Distribution Center');
                },

                addOrderMarkers() {
                    this.markers.forEach(marker => {
                        this.map.removeLayer(marker);
                    });
                    this.markers = [];

                    const priorityColors = {
                        'high': '#ef4444',
                        'medium': '#f59e0b',
                        'low': '#10b981'
                    };

                    this.orders.forEach((order, index) => {
                        const orderIcon = L.divIcon({
                            className: 'custom-marker',
                            html: `<div class="custom-marker" style="background-color: ${priorityColors[order.priority]}">${index + 1}</div>`,
                            iconSize: [24, 24],
                            iconAnchor: [12, 12]
                        });

                        const marker = L.marker([order.coordinates[0], order.coordinates[1]], {
                                icon: orderIcon
                            })
                            .addTo(this.map)
                            .bindPopup(`
                                <div class="p-2 min-w-[200px]">
                                    <strong>Order #${order.id}</strong><br>
                                    <div class="text-sm">${order.client_name}</div>
                                    <div class="text-sm text-gray-600">${order.address}</div>
                                    <div class="text-lg font-semibold text-green-600 mt-1">zł${order.total_amount}</div>
                                    <div class="text-xs uppercase font-medium" style="color: ${priorityColors[order.priority]}">${order.priority} priority</div>
                                </div>
                            `, {
                                maxWidth: 250,
                                className: 'custom-popup'
                            });

                        this.markers.push(marker);
                    });
                },

                async optimizeRoutes() {
                    this.loading = true;

                    try {
                        await this.callOptimizationAPI();
                        await new Promise(resolve => setTimeout(resolve, 1500));
                        this.optimizationResult = this.generateOptimizedRoute();

                        // Small delay before visualizing route to ensure UI updates
                        setTimeout(() => {
                            this.visualizeOptimizedRoute();
                        }, 100);

                    } catch (error) {
                        console.error('Optimization failed:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async callOptimizationAPI() {
                    console.log('Calling VROOM optimization API at 147.135.252.51:3000');
                    console.log('Driver:', this.selectedDriver);
                    console.log('Orders:', this.orders);
                    return new Promise(resolve => setTimeout(resolve, 800));
                },

                generateOptimizedRoute() {
                    const optimizedOrder = [{
                            ...this.orders[1],
                            step: 1
                        }, // Warsaw
                        {
                            ...this.orders[4],
                            step: 2
                        }, // Poznan  
                        {
                            ...this.orders[3],
                            step: 3
                        }, // Wroclaw
                        {
                            ...this.orders[0],
                            step: 4
                        }, // Krakow
                        {
                            ...this.orders[2],
                            step: 5
                        } // Gdansk
                    ];

                    const routeSteps = optimizedOrder.map((order, index) => ({
                        location: order.address,
                        description: `Deliver to ${order.client_name}`,
                        distance: index === 0 ? '0 km' : `${Math.round(Math.random() * 200 + 50)} km`,
                        duration: index === 0 ? '0 min' : `${Math.round(Math.random() * 120 + 30)} min`,
                        order_id: order.id
                    }));

                    return {
                        total_distance: 1240,
                        total_time: 480,
                        savings: 340,
                        route_steps: routeSteps,
                        driver: this.selectedDriver
                    };
                },

                visualizeOptimizedRoute() {
                    if (!this.map || !this.mapInitialized) {
                        console.warn('Map not initialized yet');
                        return;
                    }

                    // Clear existing route
                    if (this.routingControl) {
                        this.map.removeControl(this.routingControl);
                        this.routingControl = null;
                    }

                    // Create optimized waypoints
                    const routeCoordinates = [
                        [52.2297, 21.0122], // Warsaw (depot)
                        [52.2297, 21.0122], // Warsaw (order)
                        [52.4064, 16.9252], // Poznan
                        [51.1079, 17.0385], // Wroclaw
                        [50.0647, 19.9450], // Krakow
                        [54.3520, 18.6466], // Gdansk
                        [52.2297, 21.0122] // Back to depot
                    ];

                    try {
                        // Initialize routing with performance optimizations
                        this.routingControl = L.Routing.control({
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

                        this.routingControl.addTo(this.map);

                        // Fit map to show all waypoints
                        setTimeout(() => {
                            const group = new L.featureGroup(this.markers);
                            this.map.fitBounds(group.getBounds().pad(0.1));
                        }, 500);

                    } catch (error) {
                        console.error('Route visualization failed:', error);
                    }
                }
            }
        }
    </script>
</body>

</html>