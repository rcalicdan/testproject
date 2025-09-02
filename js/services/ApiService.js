import { DEMO_SERVER } from '../config/constants.js';

export class ApiService {
    constructor() {
        this.baseUrl = `http://${DEMO_SERVER.HOST}:${DEMO_SERVER.PORT}`;
    }

    async optimizeRoute(driver, orders) {
        console.log('Calling VROOM optimization API at', this.baseUrl);
        console.log('Driver:', driver);
        console.log('Orders:', orders);

        // Simulate API call
        return new Promise(resolve => setTimeout(resolve, 800));
    }

    async getDrivers() {
        // Future implementation for real API
        return Promise.resolve([]);
    }

    async getOrders() {
        // Future implementation for real API
        return Promise.resolve([]);
    }
}