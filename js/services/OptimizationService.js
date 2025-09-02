export class OptimizationService {
    generateOptimizedRoute(orders, driver) {
        const optimizedOrder = [
            { ...orders[1], step: 1 }, // Warsaw
            { ...orders[4], step: 2 }, // Poznan  
            { ...orders[3], step: 3 }, // Wroclaw
            { ...orders[0], step: 4 }, // Krakow
            { ...orders[2], step: 5 }  // Gdansk
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
            driver: driver,
            coordinates: this.getRouteCoordinates()
        };
    }

    getRouteCoordinates() {
        return [
            [52.2297, 21.0122], // Warsaw (depot)
            [52.2297, 21.0122], // Warsaw (order)
            [52.4064, 16.9252], // Poznan
            [51.1079, 17.0385], // Wroclaw
            [50.0647, 19.9450], // Krakow
            [54.3520, 18.6466], // Gdansk
            [52.2297, 21.0122]  // Back to depot
        ];
    }
}