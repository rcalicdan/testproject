function routeOptimizer() {
    const data = new RouteOptimizerData();

    data.selectedDriver = data.drivers[0];

    const mapManager = new MapManager(data);
    const optimizerService = new RouteOptimizerService(data);

    window.mapManager = mapManager;
    window.optimizerService = optimizerService;
    window.routeData = data;

    return {
        ...data,

        init() {
            console.log('RouteOptimizer component initializing...');
            console.log('Selected driver on init:', this.selectedDriver); 

            this.$nextTick(() => {
                setTimeout(() => {
                    console.log('Initializing map...');
                    mapManager.init();
                }, 100);
            });

            console.log('RouteOptimizer component initialized');
        },

        async optimizeRoutes() {
            console.log('optimizeRoutes called with driver:', this.selectedDriver); 
            console.log('Orders available:', this.orders.length);

            if (!optimizerService.canOptimize()) {
                console.warn('Cannot optimize routes - missing driver or orders');
                console.log('Driver check:', !!this.selectedDriver, this.selectedDriver);
                console.log('Orders check:', this.orders.length);
                return;
            }

            try {
                await optimizerService.optimizeRoutes();
            } catch (error) {
                console.error('Route optimization failed:', error);
            }
        },

        selectDriver(driver) {
            console.log('Driver selected:', driver);
            this.selectedDriver = driver;
        },

        focusOnOrder(orderId) {
            if (mapManager) {
                mapManager.focusOnOrder(orderId);
            }
        },

        resetOptimization() {
            if (optimizerService) {
                optimizerService.resetOptimization();
            }
        },

        refreshMap() {
            if (mapManager && this.mapInitialized) {
                mapManager.refreshMarkers();
            }
        },

        get canOptimize() {
            return optimizerService ? optimizerService.canOptimize() : false;
        },

        get optimizationSummary() {
            return optimizerService ? optimizerService.getOptimizationSummary() : null;
        },

        get totalDistance() {
            return this.optimizationResult
                ? Math.round(this.optimizationResult.total_distance)
                : 0;
        },

        get totalTimeHours() {
            return this.optimizationResult
                ? Math.round(this.optimizationResult.total_time / 60)
                : 0;
        },

        get totalTimeMinutes() {
            return this.optimizationResult
                ? this.optimizationResult.total_time % 60
                : 0;
        },

        get formattedSavings() {
            return this.optimizationResult && this.optimizationResult.savings > 0
                ? `${this.optimizationResult.savings} km saved`
                : 'No savings calculated';
        },

        get highPriorityOrders() {
            return this.orders.filter(order => order.priority === 'high');
        },

        get pendingOrdersCount() {
            return this.orders.filter(order => order.status === 'pending').length;
        },

        get totalOrderValue() {
            return this.orders.reduce((sum, order) => sum + order.total_amount, 0);
        },

        onDriverChange(event) {
            const driverId = parseInt(event.target.value);
            const driver = this.drivers.find(d => d.id === driverId);
            if (driver) {
                this.selectDriver(driver);
            }
        },

        onOrderClick(orderId) {
            this.focusOnOrder(orderId);
        },

        onMapReady() {
            console.log('Map is ready for interactions');
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

        getPriorityClass(priority) {
            const classes = {
                'high': 'bg-red-100 text-red-800 border-red-200',
                'medium': 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'low': 'bg-green-100 text-green-800 border-green-200'
            };
            return classes[priority] || 'bg-gray-100 text-gray-800 border-gray-200';
        },

        getPriorityIcon(priority) {
            const icons = {
                'high': 'fas fa-exclamation-triangle',
                'medium': 'fas fa-exclamation-circle',
                'low': 'fas fa-info-circle'
            };
            return icons[priority] || 'fas fa-circle';
        },

        getStatusClass(status) {
            const classes = {
                'pending': 'text-orange-600 bg-orange-100',
                'completed': 'text-green-600 bg-green-100',
                'in_progress': 'text-blue-600 bg-blue-100',
                'cancelled': 'text-red-600 bg-red-100'
            };
            return classes[status] || 'text-gray-600 bg-gray-100';
        },

        debugInfo() {
            return {
                driversCount: this.drivers.length,
                ordersCount: this.orders.length,
                selectedDriver: this.selectedDriver?.full_name,
                mapInitialized: this.mapInitialized,
                hasOptimization: !!this.optimizationResult,
                loading: this.loading
            };
        },

        logCurrentState() {
            console.log('Current RouteOptimizer State:', {
                selectedDriver: this.selectedDriver,
                orders: this.orders,
                optimizationResult: this.optimizationResult,
                loading: this.loading,
                mapInitialized: this.mapInitialized
            });
        }
    };
}


window.routeOptimizer = routeOptimizer;

document.addEventListener('DOMContentLoaded', function () {
    console.log('DOM loaded, RouteOptimizer ready for Alpine.js initialization');
});

window.addEventListener('error', function (event) {
    if (event.error && event.error.stack && event.error.stack.includes('routeOptimizer')) {
        console.error('RouteOptimizer Error:', event.error);
    }
});