function routeOptimizer() {
    const data = new RouteOptimizerData();

    return {
        ...data,

        selectedDriver: data.drivers[0],
        showRouteSummary: false,

        init() {
            console.log('RouteOptimizer component initializing...');

            console.log('Initial showRouteSummary:', this.showRouteSummary);
            console.log('Selected driver on init:', this.selectedDriver);

            this.$watch('loading', (value) => {
                console.log('Loading state changed:', value);
            });

            this.$watch('optimizationResult', (value) => {
                console.log('Optimization result changed:', !!value);
                if (value) {
                    console.log('Auto-showing summary...');
                    this.showRouteSummary = true;
                }
            });

            this.$watch('showRouteSummary', (value) => {
                console.log('Show route summary changed:', value);
            });

            this.$nextTick(() => {
                setTimeout(() => {
                    console.log('Initializing map...');
                    const mapManager = new MapManager(this);
                    const optimizerService = new RouteOptimizerService(this);

                    window.mapManager = mapManager;
                    window.optimizerService = optimizerService;
                    window.routeData = this;

                    mapManager.init();
                }, 100);
            });

            console.log('RouteOptimizer component initialized');
        },

        async optimizeRoutes() {
            console.log('optimizeRoutes called with driver:', this.selectedDriver);
            console.log('Orders available:', this.orders.length);

            if (!window.optimizerService || !window.optimizerService.canOptimize()) {
                console.warn('Cannot optimize routes - missing service or requirements');
                return;
            }

            this.loading = true;
            this.optimizationError = null;

            try {
                await window.optimizerService.optimizeRoutes();
                setTimeout(() => {
                    this.showRouteSummary = true;
                    console.log('Summary should now be visible:', this.showRouteSummary);
                }, 100);
            } catch (error) {
                console.error('Route optimization failed:', error);
                this.optimizationError = error.message;
            } finally {
                this.loading = false;
            }
        },

        get executiveSummary() {
            if (!this.optimizationResult) {
                console.log('No optimization result for executive summary');
                return null;
            }

            console.log('Generating executive summary...');
            const result = this.optimizationResult;
            const unoptimizedDistance = 1440; 
            const savings = result.savings || 0;
            const efficiency = Math.max(0, Math.round((savings / unoptimizedDistance) * 100));
            const carbonReduction = Math.round(savings * 0.27 * 10) / 10;
            const fuelSaved = Math.round((savings / 100) * 10 * 6.5); 

            const summary = {
                totalStops: result.total_orders || 0,
                totalDistance: `${result.total_distance || 0} km`,
                totalTime: this.formatTime(result.total_time || 0),
                estimatedFuelCost: `zł${result.estimated_fuel_cost || 0}`,
                savings: `${savings} km`,
                efficiency: `${efficiency}%`,
                carbonReduction: `${carbonReduction} kg CO2`,
                costSavings: `zł${fuelSaved}`,
                deliveryCost: `zł${Math.round((result.total_distance || 0) * 0.8)}`,
                driverCost: `zł${Math.round(((result.total_time || 0) / 60) * 25)}`,
                profitMargin: `${Math.round((this.totalOrderValue - (result.estimated_fuel_cost || 0) - ((result.total_time || 0) / 60) * 25) / this.totalOrderValue * 100)}%`,
                startTime: '08:00',
                firstDelivery: '09:30',
                lastDelivery: result.route_steps?.length ? result.route_steps[result.route_steps.length - 1]?.estimated_arrival : '16:45',
                returnTime: this.calculateReturnTime(result.total_time || 0)
            };

            console.log('Executive summary generated:', summary);
            return summary;
        },

        // Priority Breakdown - Fixed computed property
        get priorityBreakdown() {
            const breakdown = {
                high: { count: 0, value: 0, colorClass: 'bg-red-500' },
                medium: { count: 0, value: 0, colorClass: 'bg-yellow-500' },
                low: { count: 0, value: 0, colorClass: 'bg-green-500' }
            };

            this.orders.forEach(order => {
                if (breakdown[order.priority]) {
                    breakdown[order.priority].count++;
                    breakdown[order.priority].value += order.total_amount;
                }
            });

            return Object.entries(breakdown).map(([level, data]) => ({
                level,
                ...data
            }));
        },

        get totalOrderValue() {
            return this.orders.reduce((sum, order) => sum + order.total_amount, 0);
        },

        // Toggle summary visibility
        toggleSummary() {
            this.showRouteSummary = !this.showRouteSummary;
            console.log('Summary toggled to:', this.showRouteSummary);
        },

        selectDriver(driver) {
            console.log('Driver selected:', driver);
            this.selectedDriver = driver;
        },

        focusOnOrder(orderId) {
            if (window.mapManager) {
                window.mapManager.focusOnOrder(orderId);
            }
        },

        resetOptimization() {
            console.log('Resetting optimization...');
            this.optimizationResult = null;
            this.optimizationError = null;
            this.loading = false;
            this.showRouteSummary = false;

            if (window.mapManager) {
                window.mapManager.clearRoute();
            }
        },

        refreshMap() {
            if (window.mapManager && this.mapInitialized) {
                window.mapManager.refreshMarkers();
            }
        },

        // Export Summary Function
        exportSummary() {
            if (!this.optimizationResult) {
                console.warn('No optimization result to export');
                return;
            }

            const summary = this.executiveSummary;
            const exportData = {
                optimization_date: new Date().toLocaleDateString(),
                driver: this.selectedDriver.full_name,
                vehicle: this.selectedDriver.vehicle_details,
                summary: summary,
                route_details: this.optimizationResult.route_steps,
                orders: this.orders.map(order => ({
                    id: order.id,
                    client: order.client_name,
                    address: order.address,
                    value: order.total_amount,
                    priority: order.priority
                }))
            };

            // Create and download JSON file
            const dataStr = JSON.stringify(exportData, null, 2);
            const dataBlob = new Blob([dataStr], { type: 'application/json' });
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `route-summary-${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            console.log('Route summary exported');
        },

        // Calculate return time based on total route time
        calculateReturnTime(totalMinutes) {
            const startTime = new Date();
            startTime.setHours(8, 0, 0, 0); // 8 AM start
            const returnTime = new Date(startTime.getTime() + (totalMinutes * 60000));
            return returnTime.toLocaleTimeString('pl-PL', {
                hour: '2-digit',
                minute: '2-digit'
            });
        },

        formatCurrency(amount) {
            return `zł${amount.toLocaleString('pl-PL')}`;
        },

        formatTime(minutes) {
            const hours = Math.floor(minutes / 60);
            const mins = minutes % 60;
            return hours > 0 ? `${hours}h ${mins}m` : `${mins}m`;
        },

        formatDistance(meters) {
            return meters >= 1000
                ? `${Math.round(meters / 1000)} km`
                : `${meters} m`;
        },

        debugSummaryState() {
            console.log('=== Summary Debug Info ===');
            console.log('showRouteSummary:', this.showRouteSummary);
            console.log('optimizationResult exists:', !!this.optimizationResult);
            console.log('executiveSummary:', this.executiveSummary);
            console.log('priorityBreakdown:', this.priorityBreakdown);
            console.log('========================');
        }
    };
}

window.routeOptimizer = routeOptimizer;

document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded, RouteOptimizer ready for Alpine.js initialization');
});