class OTPService {
    constructor() {
        this.baseURL = '';
        this.currentOrder = null;
        this.pollInterval = null;
        this.userBalance = 0;
        
        this.loadServices();
        this.updateBalance();
    }
    
    async loadServices() {
        try {
            const response = await fetch('/api/services');
            const services = await response.json();
            
            this.displayServices(services);
        } catch (error) {
            console.error('Error loading services:', error);
        }
    }
    
    displayServices(services) {
        const grid = document.getElementById('servicesGrid');
        grid.innerHTML = '';
        
        services.forEach(service => {
            const card = document.createElement('div');
            card.className = 'service-card';
            
            card.innerHTML = `
                <div class="service-name">${service.name}</div>
                <div class="service-price">₹${service.price}</div>
                <button class="buy-btn" onclick="otpService.buyNumber('${service.id}')">
                    Buy Number
                </button>
            `;
            
            grid.appendChild(card);
        });
    }
    
    async buyNumber(serviceId) {
        try {
            const response = await fetch('/api/order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    service: serviceId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.currentOrder = result.order;
                this.showOrderStatus();
                this.startSMSPolling();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error ordering number:', error);
            alert('Service temporarily unavailable');
        }
    }
    
    showOrderStatus() {
        document.getElementById('currentNumber').textContent = 
            `Phone Number: ${this.currentOrder.number}`;
        document.getElementById('orderStatus').classList.remove('hidden');
    }
    
    startSMSPolling() {
        this.pollInterval = setInterval(async () => {
            await this.checkSMS();
        }, 5000); // Check every 5 seconds
    }
    
    async checkSMS() {
        if (!this.currentOrder) return;
        
        try {
            const response = await fetch(`/api/sms/${this.currentOrder.id}`);
            const result = await response.json();
            
            if (result.success && result.sms) {
                document.getElementById('smsInbox').innerHTML = 
                    `<strong>SMS Received:</strong><br>${result.sms}`;
                clearInterval(this.pollInterval);
            }
        } catch (error) {
            console.error('Error checking SMS:', error);
        }
    }
    
    closeOrder() {
        if (this.pollInterval) {
            clearInterval(this.pollInterval);
        }
        this.currentOrder = null;
        document.getElementById('orderStatus').classList.add('hidden');
        this.updateBalance();
    }
    
    async updateBalance() {
        try {
            const response = await fetch('/api/balance');
            const result = await response.json();
            
            if (result.success) {
                this.userBalance = result.balance;
                document.getElementById('userBalance').textContent = this.userBalance.toFixed(2);
            }
        } catch (error) {
            console.error('Error updating balance:', error);
        }
    }
}

// Initialize service
const otpService = new OTPService();

// Utility functions
function closeOrder() {
    otpService.closeOrder();
}
