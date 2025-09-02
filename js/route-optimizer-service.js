class RouteOptimizerService {
    constructor(data) {
        this.data = data;
        this.apiEndpoint = '147.135.252.51:3000';
        this.mockDelay = 1500;
    }

    async optimizeRoutes() {
        if (!this.data.selectedDriver) {
            throw new Error('No driver selected');
        }

        if (this.data.orders.length === 0) {
            throw new Error('No orders to optimize');
        }

        this.data.loading = true;
        console.log('Starting route optimization...');

        try {
            await this.callOptimizationAPI();
            await new Promise(resolve => setTimeout(resolve, this.mockDelay));

            this.data.optimizationResult = this.generateOptimizedRoute();

            console.log('Route optimization completed:', this.data.optimizationResult);

            setTimeout(() => {
                if (window.mapManager) {
                    window.mapManager.visualizeOptimizedRoute();
                }
            }, 100);

        } catch (error) {
            console.error('Optimization failed:', error);
            this.handleOptimizationError(error);
        } finally {
            this.data.loading = false;
        }
    }

    async callOptimizationAPI() {
        console.log(`Calling VROOM optimization API at ${this.apiEndpoint}`);
        console.log('Selected Driver:', this.data.selectedDriver);
        console.log('Orders to optimize:', this.data.orders.length);

        this.data.orders.forEach((order, index) => {
            console.log(`Order ${index + 1}:`, {
                id: order.id,
                client: order.client_name,
                address: order.address,
                coordinates: order.coordinates,
                priority: order.priority,
                amount: order.total_amount
            });
        });

        // TODO: Replace with actual API call
        // const response = await fetch(`http://${this.apiEndpoint}/optimize`, {
        //     method: 'POST',
        //     headers: {
        //         'Content-Type': 'application/json',
        //     },
        //     body: JSON.stringify({
        //         driver: this.data.selectedDriver,
        //         orders: this.data.orders,
        //         depot: [52.2297, 21.0122] // Warsaw depot coordinates
        //     })
        // });
        // return await response.json();

        return new Promise(resolve => setTimeout(resolve, 800));
    }

    generateOptimizedRoute() {
        console.log('Generating optimized route...');

        // Create optimized order based on geographical optimization
        // This simulates the result of TSP (Traveling Salesman Problem) solving
        const optimizedOrder = [
            { ...this.data.orders[1], step: 1 }, // Warsaw - closest to depot
            { ...this.data.orders[4], step: 2 }, // Poznan - west from Warsaw
            { ...this.data.orders[3], step: 3 }, // Wroclaw - southwest
            { ...this.data.orders[0], step: 4 }, // Krakow - south
            { ...this.data.orders[2], step: 5 }  // Gdansk - north (last stop before return)
        ];

        // Calculate cumulative distances and times
        let cumulativeDistance = 0;
        let cumulativeTime = 0;

        const routeSteps = optimizedOrder.map((order, index) => {
            const segmentDistance = this.calculateSegmentDistance(index, optimizedOrder);
            const segmentTime = this.calculateSegmentTime(segmentDistance);

            cumulativeDistance += segmentDistance;
            cumulativeTime += segmentTime;

            return {
                step: index + 1,
                location: order.address,
                description: `Deliver to ${order.client_name}`,
                distance: index === 0 ? '0 km' : `${segmentDistance} km`,
                duration: index === 0 ? '0 min' : `${segmentTime} min`,
                cumulative_distance: `${cumulativeDistance} km`,
                cumulative_time: `${cumulativeTime} min`,
                order_id: order.id,
                client_name: order.client_name,
                amount: order.total_amount,
                priority: order.priority,
                estimated_arrival: this.calculateEstimatedArrival(cumulativeTime),
                coordinates: order.coordinates
            };
        });

        const unoptimizedDistance = this.calculateUnoptimizedDistance();
        const savings = unoptimizedDistance - cumulativeDistance;

        const result = {
            total_distance: cumulativeDistance,
            total_time: cumulativeTime,
            savings: Math.max(0, savings),
            route_steps: routeSteps,
            driver: this.data.selectedDriver,
            optimization_timestamp: new Date().toISOString(),
            total_orders: optimizedOrder.length,
            total_value: optimizedOrder.reduce((sum, order) => sum + order.total_amount, 0),
            estimated_fuel_cost: this.calculateFuelCost(cumulativeDistance),
            carbon_footprint: this.calculateCarbonFootprint(cumulativeDistance)
        };

        return result;
    }

    calculateSegmentDistance(index, optimizedOrder) {
        const distances = [
            0,   // Start at depot
            120, // Warsaw to Poznan
            180, // Poznan to Wroclaw  
            270, // Wroclaw to Krakow
            480  // Krakow to Gdansk
        ];

        return distances[index] + Math.round(Math.random() * 20 - 10); // Add some variation
    }

    calculateSegmentTime(distance) {
        const baseTime = Math.round((distance / 80) * 60); // Convert to minutes
        const stopTime = 15; // 15 minutes per stop for delivery
        return baseTime + stopTime;
    }

    calculateUnoptimizedDistance() {
        return Math.round(1240 + Math.random() * 400 + 200); // 1440-1840 km
    }

    calculateEstimatedArrival(cumulativeTimeMinutes) {
        const startTime = new Date();
        startTime.setHours(8, 0, 0, 0);

        const arrivalTime = new Date(startTime.getTime() + (cumulativeTimeMinutes * 60000));

        return arrivalTime.toLocaleTimeString('pl-PL', {
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    calculateFuelCost(distanceKm) {
        const fuelConsumptionPer100km = 10;
        const fuelPricePerLiter = 6.5;

        return Math.round((distanceKm / 100) * fuelConsumptionPer100km * fuelPricePerLiter);
    }

    calculateCarbonFootprint(distanceKm) {
        const co2PerKm = 0.27;
        return Math.round(distanceKm * co2PerKm * 10) / 10;
    }

    handleOptimizationError(error) {
        console.error('Route optimization error:', error);

        this.data.optimizationError = {
            message: error.message || 'Optimization failed',
            timestamp: new Date().toISOString(),
            canRetry: true
        };

        this.data.optimizationResult = null;

        setTimeout(() => {
            if (this.data.optimizationError) {
                this.data.optimizationError = null;
            }
        }, 5000);
    }

    canOptimize() {
        return this.data.selectedDriver &&
            this.data.selectedDriver.id &&
            this.data.orders.length > 0 &&
            !this.data.loading;
    }

    getOptimizationSummary() {
        if (!this.data.optimizationResult) return null;

        const result = this.data.optimizationResult;
        return {
            totalDistance: `${result.total_distance} km`,
            totalTime: `${Math.round(result.total_time / 60)}h ${result.total_time % 60}m`,
            savings: `${result.savings} km saved`,
            fuelCost: `zł${result.estimated_fuel_cost}`,
            co2Saved: `${(result.savings * 0.27).toFixed(1)} kg CO2`,
            stops: result.total_orders
        };
    }

    resetOptimization() {
        this.data.optimizationResult = null;
        this.data.optimizationError = null;
        this.data.loading = false;

        if (window.mapManager) {
            window.mapManager.clearRoute();
        }
    }
}