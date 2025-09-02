import { DRIVERS, ORDERS } from './data/mockData.js';
import { ApiService } from './services/ApiService.js';
import { MapService } from './services/MapService.js';
import { OptimizationService } from './services/OptimizationService.js';
import { CONFIG } from './config/constants.js';

function routeOptimizer() {
    return {
        apiService: new ApiService(),
        mapService: new MapService(),
        optimizationService: new OptimizationService(),

        selectedDriver: {},
        loading: false,
        optimizationResult: null,
        drivers: DRIVERS,
        orders: ORDERS,

        init() {
            this.selectedDriver = this.drivers[0];
            this.$nextTick(() => {
                setTimeout(() => {
                    this.mapService.initMap('map');
                    this.mapService.addOrderMarkers(this.orders);
                }, CONFIG.TIMING.MAP_INIT_DELAY);
            });
        },

        async optimizeRoutes() {
            this.loading = true;

            try {
                await this.apiService.optimizeRoute(this.selectedDriver, this.orders);
                await new Promise(resolve => setTimeout(resolve, CONFIG.TIMING.OPTIMIZATION_DELAY));
                
                this.optimizationResult = this.optimizationService.generateOptimizedRoute(
                    this.orders, 
                    this.selectedDriver
                );

                setTimeout(() => {
                    this.mapService.visualizeRoute(this.optimizationResult.coordinates);
                }, CONFIG.TIMING.ROUTE_VISUALIZATION_DELAY);

            } catch (error) {
                console.error('Optimization failed:', error);
            } finally {
                this.loading = false;
            }
        }
    };
}

window.routeOptimizer = routeOptimizer;