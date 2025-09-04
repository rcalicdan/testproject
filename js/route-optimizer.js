class RouteOptimizerData {
    constructor() {
        this.drivers = [
            {
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
        ];

        this.orders = [
            {
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
        ];

        this.selectedDriver = null; 
        this.loading = false;
        this.optimizationResult = null;
        this.optimizationError = null;
        this.showRouteSummary = false; 
        this.map = null;
        this.markers = [];
        this.routingControl = null;
        this.mapInitialized = false;
    }

    get totalOrders() {
        return this.orders.length;
    }

    get totalValue() {
        return this.orders.reduce((sum, order) => sum + order.total_amount, 0);
    }

    get pendingOrders() {
        return this.orders.filter(order => order.status === 'pending');
    }

    get highPriorityOrders() {
        return this.orders.filter(order => order.priority === 'high');
    }

    get mediumPriorityOrders() {
        return this.orders.filter(order => order.priority === 'medium');
    }

    get lowPriorityOrders() {
        return this.orders.filter(order => order.priority === 'low');
    }
}