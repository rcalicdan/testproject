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
    <link rel="stylesheet" href="css/styles.css" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Leaflet Routing Machine JS -->
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js"></script>
    <script src="js/route-optimizer.js"></script>
    <script src="js/map-manager.js"></script>
    <script src="js/route-optimizer-service.js"></script>
    <script src="js/alpine-component.js"></script>
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
                            Poland Route Optimization
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
                                    <div class="text-2xl font-bold text-green-600" x-text="Math.round((optimizationResult?.total_distance || 0)) + ' km'"></div>
                                    <div class="text-xs text-green-700 uppercase">Total Distance</div>
                                </div>
                                <div class="bg-blue-50 p-3 rounded-lg text-center">
                                    <div class="text-2xl font-bold text-blue-600" x-text="Math.round((optimizationResult?.total_time || 0) / 60) + 'h ' + Math.round((optimizationResult?.total_time || 0) % 60) + 'm'"></div>
                                    <div class="text-xs text-blue-700 uppercase">Total Time</div>
                                </div>
                            </div>

                            <!-- Summary Actions -->
                            <div x-show="optimizationResult" class="space-y-2">
                                <button @click="showRouteSummary = !showRouteSummary"
                                    class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                    <span x-show="!showRouteSummary">
                                        <i class="fas fa-chart-bar mr-2"></i>View Summary
                                    </span>
                                    <span x-show="showRouteSummary">
                                        <i class="fas fa-eye-slash mr-2"></i>Hide Summary
                                    </span>
                                </button>
                                <button @click="resetOptimization()"
                                    class="w-full bg-red-50 hover:bg-red-100 text-red-600 py-2 px-4 rounded-lg text-sm font-medium transition-colors">
                                    <i class="fas fa-redo mr-2"></i>Reset Route
                                </button>
                                <!-- Debug button - remove in production -->
                                <button @click="debugSummaryState()"
                                    class="w-full bg-yellow-50 hover:bg-yellow-100 text-yellow-600 py-1 px-4 rounded text-xs">
                                    Debug Summary
                                </button>
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

                    <!-- Route Summary Dashboard -->
                    <div x-show="optimizationResult && showRouteSummary"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform translate-y-4"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        class="bg-white rounded-xl custom-shadow p-6">

                        <!-- Debug info - remove in production -->
                        <div class="mb-4 p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                            Debug: Summary visible = <span x-text="showRouteSummary"></span>,
                            Has result = <span x-text="!!optimizationResult"></span>,
                            Executive summary = <span x-text="!!executiveSummary"></span>
                        </div>

                        <h2 class="text-xl font-semibold text-gray-800 mb-6 flex items-center">
                            <i class="fas fa-chart-bar text-primary mr-2"></i>
                            Route Summary & Analytics
                            <button @click="exportSummary()" class="ml-auto text-sm bg-primary text-white px-3 py-1 rounded-lg hover:bg-opacity-90">
                                <i class="fas fa-download mr-1"></i>Export
                            </button>
                        </h2>

                        <!-- Executive Summary Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                            <div class="text-center p-4 bg-blue-50 rounded-xl border border-blue-200">
                                <div class="text-3xl font-bold text-blue-600 mb-1" x-text="executiveSummary?.totalStops || '0'"></div>
                                <div class="text-xs text-blue-700 uppercase font-medium">Total Stops</div>
                                <div class="text-xs text-blue-600 mt-1">+ Depot Return</div>
                            </div>
                            <div class="text-center p-4 bg-green-50 rounded-xl border border-green-200">
                                <div class="text-3xl font-bold text-green-600 mb-1" x-text="executiveSummary?.totalDistance || '0 km'"></div>
                                <div class="text-xs text-green-700 uppercase font-medium">Total Distance</div>
                                <div class="text-xs text-green-600 mt-1" x-text="(executiveSummary?.savings || '0 km') + ' saved'"></div>
                            </div>
                            <div class="text-center p-4 bg-purple-50 rounded-xl border border-purple-200">
                                <div class="text-3xl font-bold text-purple-600 mb-1" x-text="executiveSummary?.totalTime || '0h'"></div>
                                <div class="text-xs text-purple-700 uppercase font-medium">Total Time</div>
                                <div class="text-xs text-purple-600 mt-1">Including stops</div>
                            </div>
                            <div class="text-center p-4 bg-orange-50 rounded-xl border border-orange-200">
                                <div class="text-3xl font-bold text-orange-600 mb-1" x-text="executiveSummary?.estimatedFuelCost || 'zł0'"></div>
                                <div class="text-xs text-orange-700 uppercase font-medium">Fuel Cost</div>
                                <div class="text-xs text-orange-600 mt-1">Estimated</div>
                            </div>
                        </div>

                        <!-- Rest of the summary content remains the same -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Optimization Impact -->
                            <div class="bg-gradient-to-br from-green-50 to-emerald-50 p-5 rounded-xl border border-green-200">
                                <h3 class="font-semibold text-green-800 mb-4 flex items-center">
                                    <i class="fas fa-leaf mr-2"></i>Optimization Impact
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-green-700">Distance Saved:</span>
                                        <span class="font-bold text-green-600" x-text="executiveSummary?.savings || '0 km'"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-green-700">CO2 Reduction:</span>
                                        <span class="font-bold text-green-600" x-text="executiveSummary?.carbonReduction || '0 kg'"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-green-700">Efficiency Gain:</span>
                                        <span class="font-bold text-green-600" x-text="executiveSummary?.efficiency || '0%'"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-green-700">Cost Savings:</span>
                                        <span class="font-bold text-green-600" x-text="executiveSummary?.costSavings || 'zł0'"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Financial Overview -->
                            <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-5 rounded-xl border border-blue-200">
                                <h3 class="font-semibold text-blue-800 mb-4 flex items-center">
                                    <i class="fas fa-dollar-sign mr-2"></i>Financial Overview
                                </h3>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-blue-700">Total Order Value:</span>
                                        <span class="font-bold text-blue-600" x-text="formatCurrency(totalOrderValue)"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-blue-700">Delivery Cost:</span>
                                        <span class="font-bold text-blue-600" x-text="executiveSummary?.deliveryCost || 'zł0'"></span>
                                    </div>
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-blue-700">Driver Time Cost:</span>
                                        <span class="font-bold text-blue-600" x-text="executiveSummary?.driverCost || 'zł0'"></span>
                                    </div>
                                    <div class="flex justify-between items-center border-t pt-2">
                                        <span class="text-sm font-medium text-blue-800">Profit Margin:</span>
                                        <span class="font-bold text-blue-600" x-text="executiveSummary?.profitMargin || '0%'"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                    <div class="route-card flex items-center p-4 border rounded-lg hover:shadow-md transition-shadow">
                                        <div class="flex-shrink-0 w-8 h-8 bg-primary text-white rounded-full flex items-center justify-center font-semibold text-sm"
                                            x-text="index + 1"></div>
                                        <div class="ml-4 flex-1">
                                            <div class="font-medium text-gray-800" x-text="step.location"></div>
                                            <div class="text-sm text-gray-600" x-text="step.description"></div>
                                            <div class="text-xs text-primary mt-1" x-text="'ETA: ' + step.estimated_arrival"></div>
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
        <div x-show="loading"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="loading-overlay fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center"
            style="z-index: 9999 !important;">

            <div class="absolute inset-0 bg-black bg-opacity-40" style="backdrop-filter: blur(8px);"></div>

            <div class="loading-glass-effect rounded-2xl p-8 text-center max-w-sm mx-4 relative transform">
                <div class="relative mx-auto mb-6">
                    <div class="animate-spin rounded-full h-20 w-20 border-4 border-primary border-t-transparent mx-auto"></div>
                    <div class="absolute inset-0 rounded-full border-4 border-primary opacity-20"></div>
                </div>

                <h3 class="text-xl font-bold text-gray-800 mb-3">Optimizing Routes</h3>
                <p class="text-gray-600 mb-4">Calculating the best delivery path using advanced algorithms...</p>

                <div class="space-y-2">
                    <div class="flex items-center justify-center text-sm text-gray-500">
                        <i class="fas fa-map-marked-alt mr-2 text-primary"></i>
                        Analyzing delivery locations
                    </div>
                    <div class="flex items-center justify-center text-sm text-gray-500">
                        <i class="fas fa-route mr-2 text-primary"></i>
                        Computing optimal path
                    </div>
                    <div class="flex items-center justify-center text-sm text-gray-500">
                        <i class="fas fa-clock mr-2 text-primary"></i>
                        Estimating delivery times
                    </div>
                </div>

                <div class="mt-6">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-primary to-secondary h-2 rounded-full animate-pulse"
                            style="width: 100%; animation: loading-progress 2s ease-in-out infinite;">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>